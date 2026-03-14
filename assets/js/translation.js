class TranslationSystem {
    constructor() {
        this.translations = {};
        this.currentLanguage = 'vi';
        this.loadedLanguages = new Set();
        this.fallbackLanguage = 'vi';
    }

    async init() {
        console.log('Initializing translation system...');
        
        // Get saved language from localStorage or browser
        const savedLanguage = localStorage.getItem('language');
        const browserLanguage = navigator.language.split('-')[0];
        this.currentLanguage = savedLanguage || browserLanguage || this.fallbackLanguage;
        
        console.log(`Initial language: ${this.currentLanguage} (saved: ${savedLanguage}, browser: ${browserLanguage})`);
        
        // Load language files
        await this.loadLanguage('vi');
        await this.loadLanguage('en');
        await this.loadLanguage('ko');
        
        // Apply current language
        this.applyTranslations(this.currentLanguage);
        
        // Update language switcher
        this.updateLanguageSwitcher();
        
        console.log(`Translation system initialized with language: ${this.currentLanguage}`);
        console.log('Loaded languages:', Array.from(this.loadedLanguages));
        console.log('Available translations:', Array.from(this.loadedLanguages));
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
            
            // Parse the language file
            const translationObj = this.parseLanguageFile(text);
            
            if (translationObj) {
                this.translations[lang] = translationObj;
                this.loadedLanguages.add(lang);
                console.log(`Language ${lang} loaded successfully with ${Object.keys(translationObj).length} keys`);
            }
        } catch (error) {
            console.error(`Error loading language ${lang}:`, error);
        }
    }

    parseLanguageFile(text) {
        try {
            // Remove comments and clean up the file
            const cleanText = text
                .replace(/\/\*[\s\S]*?\*\//g, '') // Remove block comments
                .replace(/\/\/.*$/gm, '') // Remove line comments
                .trim();
            
            // Try different patterns for language file formats
            let match;
            
            // Pattern 1: const translations = {...}
            match = cleanText.match(/const\s+translations\s*=\s*({[\s\S]*})/);
            if (match) {
                const objectText = match[1];
                return new Function('return ' + objectText)();
            }
            
            // Pattern 2: window.vi = {...} or window.en = {...} or window.ko = {...}
            match = cleanText.match(/window\.\w+\s*=\s*({[\s\S]*})/);
            if (match) {
                const objectText = match[1];
                return new Function('return ' + objectText)();
            }
            
            // Pattern 3: Just {...} object
            match = cleanText.match(/^({[\s\S]*})$/);
            if (match) {
                const objectText = match[1];
                return new Function('return ' + objectText)();
            }
            
            console.error('No valid translation object found in language file');
            return null;
        } catch (error) {
            console.error('Error parsing language file:', error);
            return null;
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
        
        return fallbackText || key;
    }

    async switchLanguage(lang) {
        console.log(`Switching to language: ${lang}`);
        
        if (!this.loadedLanguages.has(lang)) {
            console.log(`Language ${lang} not loaded yet, loading...`);
            await this.loadLanguage(lang);
        }
        
        this.currentLanguage = lang;
        localStorage.setItem('language', lang);
        
        // Apply translations to all elements
        this.applyTranslations(lang);
        
        console.log(`Language switched to: ${lang}`);
    }

    applyTranslations(lang) {
        console.log(`Applying translations for language: ${lang}`);
        console.log('Available translations for this language:', this.translations[lang] ? 'Yes' : 'No');
        
        // Find all elements with data-translate attribute
        const elements = document.querySelectorAll('[data-translate]');
        console.log(`Found ${elements.length} elements to translate`);
        
        elements.forEach((element, index) => {
            const key = element.getAttribute('data-translate');
            const translation = this.translations[lang]?.[key] || key;
            
            console.log(`Element ${index}: key="${key}", translation="${translation}"`);
            console.log(`Before apply - element.textContent: "${element.textContent}"`);
            
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
            
            console.log(`After apply - element.textContent: "${element.textContent}"`);
        });
        
        console.log(`Applied ${lang} to ${elements.length} elements`);
        
        // Test specific dashboard element
        const dashboardElement = document.querySelector('[data-translate="dashboard"]');
        if (dashboardElement) {
            console.log('Dashboard element test:');
            console.log('- Tag:', dashboardElement.tagName);
            console.log('- HTML:', dashboardElement.innerHTML);
            console.log('- Text:', dashboardElement.textContent);
            console.log('- Parent HTML:', dashboardElement.parentElement.innerHTML);
        }
        
        // Update page title
        const titleKey = document.querySelector('title')?.getAttribute('data-translate');
        if (titleKey) {
            document.title = this.translations[lang]?.[titleKey] || document.title;
        }
        
        console.log(`Applied ${lang} to ${elements.length} elements`);
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

    getLoadedLanguages() {
        return Array.from(this.loadedLanguages);
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
    
    console.log('Translation system initialized successfully!');
    console.log('Available translations:', Object.keys(window.translationSystem.translations));
    console.log('Current language:', window.translationSystem.currentLanguage);
    
    // Create global t function for easy access
    window.t = (key, fallbackText = null) => {
        return window.translationSystem.translate(key, fallbackText);
    };
    
    // Add setLanguage method to t function for convenience
    window.t.setLanguage = (lang) => {
        window.translationSystem.switchLanguage(lang);
    };
    
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
    
    console.log('Translation system initialization complete');
});

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { TranslationSystem, t };
}
