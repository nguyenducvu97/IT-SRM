// Translation System for IT Service Request Management
class TranslationSystem {
    constructor() {
        this.currentLanguage = 'vi'; // Default language
        this.translations = {};
        this.loadedLanguages = new Set();
        // Don't call init() here - let it be called manually
    }

    async init() {
        console.log('Initializing translation system...');
        
        // Load saved language preference or detect browser language
        const savedLanguage = localStorage.getItem('preferred_language');
        const browserLanguage = this.detectBrowserLanguage();
        
        this.currentLanguage = savedLanguage || browserLanguage || 'vi';
        console.log(`Initial language: ${this.currentLanguage} (saved: ${savedLanguage}, browser: ${browserLanguage})`);
        
        // Load all language files
        console.log('Loading language files...');
        await this.loadLanguage('vi');
        await this.loadLanguage('en');
        await this.loadLanguage('ko');
        
        // Apply current language
        this.applyLanguage(this.currentLanguage);
        
        console.log(`Translation system initialized with language: ${this.currentLanguage}`);
        console.log('Loaded languages:', Array.from(this.loadedLanguages));
        console.log('Available translations:', Object.keys(this.translations));
    }

    detectBrowserLanguage() {
        const browserLang = navigator.language || navigator.userLanguage;
        
        if (browserLang.startsWith('vi')) return 'vi';
        if (browserLang.startsWith('ko')) return 'ko';
        if (browserLang.startsWith('en')) return 'en';
        
        return 'vi'; // Default to Vietnamese
    }

    async loadLanguage(lang) {
        if (this.loadedLanguages.has(lang)) {
            console.log(`Language ${lang} already loaded`);
            return Promise.resolve();
        }

        console.log(`Loading language ${lang}...`);
        
        try {
            const response = await fetch(`assets/js/languages/${lang}.js`);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const text = await response.text();
            console.log(`Fetched ${lang}.js, length: ${text.length} chars`);
            
            // Create a script element to properly load the language file
            const script = document.createElement('script');
            script.type = 'text/javascript';
            script.text = text;
            document.head.appendChild(script);
            
            // Store the translation object
            let translationObj = null;
            switch(lang) {
                case 'vi':
                    translationObj = window.vi || vi;
                    break;
                case 'en':
                    translationObj = window.en || en;
                    break;
                case 'ko':
                    translationObj = window.ko || ko;
                    break;
            }
            
            if (translationObj) {
                this.translations[lang] = translationObj;
                this.loadedLanguages.add(lang);
                console.log(`Language ${lang} loaded successfully with ${Object.keys(translationObj).length} keys`);
            } else {
                throw new Error(`Translation object for ${lang} not found`);
            }
        } catch (error) {
            console.error(`Failed to load language ${lang}:`, error);
        }
    }

    translate(key, fallbackText = null) {
        const translation = this.translations[this.currentLanguage]?.[key];
        
        if (translation) {
            return translation;
        }
        
        // Fallback to Vietnamese if current language doesn't have the key
        const vietnameseTranslation = this.translations['vi']?.[key];
        if (vietnameseTranslation) {
            return vietnameseTranslation;
        }
        
        // Return fallback text or the key itself
        return fallbackText || key;
    }

    async switchLanguage(lang) {
        console.log(`Switching to language: ${lang}`);
        
        if (!this.loadedLanguages.has(lang)) {
            console.log(`Language ${lang} not loaded yet, loading...`);
            await this.loadLanguage(lang);
        }
        
        this.currentLanguage = lang;
        localStorage.setItem('preferred_language', lang);
        
        console.log(`Applying language ${lang}...`);
        this.applyLanguage(lang);
        this.updateLanguageSwitcher();
        
        console.log(`Language switched to: ${lang}`);
        
        // Trigger custom event for other components
        document.dispatchEvent(new CustomEvent('languageChanged', {
            detail: { language: lang }
        }));
    }

    applyLanguage(lang) {
        // Update all elements with data-translate attribute
        const elements = document.querySelectorAll('[data-translate]');
        
        elements.forEach(element => {
            const key = element.getAttribute('data-translate');
            const translation = this.translations[lang]?.[key] || key;
            
            // Handle different element types
            if (element.tagName === 'INPUT' || element.tagName === 'TEXTAREA') {
                if (element.type === 'submit' || element.type === 'button') {
                    element.value = translation;
                } else {
                    element.placeholder = translation;
                }
            } else if (element.tagName === 'OPTION') {
                element.textContent = translation;
            } else if (element.tagName === 'BUTTON' && element.type !== 'submit') {
                // Handle buttons with inner HTML
                if (element.innerHTML.includes('<i class=')) {
                    // Preserve icons, update text
                    const iconMatch = element.innerHTML.match(/(<i[^>]*>.*?<\/i>)/);
                    if (iconMatch) {
                        element.innerHTML = iconMatch[1] + ' ' + translation;
                    }
                } else {
                    element.textContent = translation;
                }
            } else {
                // Handle elements with nested content
                if (element.children.length > 0) {
                    // Only translate text nodes, preserve child elements
                    const textNodes = [];
                    const walker = document.createTreeWalker(
                        element,
                        NodeFilter.SHOW_TEXT,
                        null,
                        false
                    );
                    
                    let node;
                    while (node = walker.nextNode()) {
                        if (node.textContent.trim()) {
                            textNodes.push(node);
                        }
                    }
                    
                    textNodes.forEach(textNode => {
                        const text = textNode.textContent.trim();
                        if (text) {
                            const translatedText = this.translations[lang]?.[text] || text;
                            textNode.textContent = translatedText;
                        }
                    });
                } else {
                    element.textContent = translation;
                }
            }
        });
        
        // Update page title
        const titleKey = document.querySelector('title')?.getAttribute('data-translate');
        if (titleKey) {
            document.title = this.translations[lang]?.[titleKey] || document.title;
        }
        
        // Update HTML lang attribute
        document.documentElement.lang = lang;
        
        console.log(`Applied language ${lang} to ${elements.length} elements`);
    }

    updateLanguageSwitcher() {
        const switcher = document.getElementById('languageSwitcher');
        if (switcher) {
            switcher.value = this.currentLanguage;
        }
    }

    getCurrentLanguage() {
        return this.currentLanguage;
    }

    getAvailableLanguages() {
        return [
            { code: 'vi', name: this.translate('vietnamese', 'Tiếng Việt'), flag: '🇻🇳' },
            { code: 'en', name: this.translate('english', 'English'), flag: '🇺🇸' },
            { code: 'ko', name: this.translate('korean', '한국어'), flag: '🇰🇷' }
        ];
    }
}

// Global translation function for easy access
function t(key, fallbackText = null) {
    if (window.translationSystem) {
        return window.translationSystem.translate(key, fallbackText);
    }
    return fallbackText || key;
}

// Initialize translation system
document.addEventListener('DOMContentLoaded', async () => {
    console.log('DOM loaded, initializing translation system...');
    window.translationSystem = new TranslationSystem();
    
    // Wait for initialization to complete
    await window.translationSystem.init();
    
    // Add language switcher event listener
    const languageSwitcher = document.getElementById('languageSwitcher');
    if (languageSwitcher) {
        languageSwitcher.addEventListener('change', (e) => {
            console.log('Language switcher changed to:', e.target.value);
            window.translationSystem.switchLanguage(e.target.value);
        });
    } else {
        console.log('Language switcher not found');
    }
});

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { TranslationSystem, t };
}
