// Generated notification sound - simple beep
// This is a placeholder - in production, replace with actual sound files

// Since we can't generate actual audio files, we'll use Web Audio API
// to create notification sounds programmatically

class NotificationSounds {
    constructor() {
        this.audioContext = null;
        this.initAudioContext();
    }
    
    initAudioContext() {
        try {
            window.AudioContext = window.AudioContext || window.webkitAudioContext;
            this.audioContext = new AudioContext();
        } catch (e) {
            console.error('Web Audio API not supported:', e);
        }
    }
    
    playNotificationSound(type = 'info') {
        if (!this.audioContext) return;
        
        const oscillator = this.audioContext.createOscillator();
        const gainNode = this.audioContext.createGain();
        
        // Different frequencies for different notification types
        const frequencies = {
            'info': 800,      // Standard notification
            'success': 600,    // Success sound (lower)
            'warning': 1000,    // Warning sound (higher)
            'error': 400        // Error sound (lowest)
        };
        
        oscillator.frequency.setValueAtTime(frequencies[type] || 800, this.audioContext.currentTime);
        oscillator.type = 'sine';
        
        // Connect nodes
        oscillator.connect(gainNode);
        gainNode.connect(this.audioContext.destination);
        
        // Set volume
        gainNode.gain.setValueAtTime(0.1, this.audioContext.currentTime);
        
        // Start and stop
        oscillator.start(this.audioContext.currentTime);
        oscillator.stop(this.audioContext.currentTime + 0.2); // 200ms beep
        
        // Create multiple beeps for different types
        if (type === 'warning') {
            // Double beep for warning
            setTimeout(() => {
                const osc2 = this.audioContext.createOscillator();
                const gain2 = this.audioContext.createGain();
                
                osc2.frequency.setValueAtTime(1000, this.audioContext.currentTime);
                osc2.type = 'sine';
                osc2.connect(gain2);
                gain2.connect(this.audioContext.destination);
                gain2.gain.setValueAtTime(0.1, this.audioContext.currentTime);
                
                osc2.start(this.audioContext.currentTime);
                osc2.stop(this.audioContext.currentTime + 0.2);
            }, 300);
        } else if (type === 'error') {
            // Triple beep for error
            [0, 300, 600].forEach(delay => {
                setTimeout(() => {
                    const osc = this.audioContext.createOscillator();
                    const gain = this.audioContext.createGain();
                    
                    osc.frequency.setValueAtTime(400, this.audioContext.currentTime);
                    osc.type = 'sine';
                    osc.connect(gain);
                    gain.connect(this.audioContext.destination);
                    gain.gain.setValueAtTime(0.1, this.audioContext.currentTime);
                    
                    osc.start(this.audioContext.currentTime);
                    osc.stop(this.audioContext.currentTime + 0.2);
                }, delay);
            });
        }
    }
}

// Export for use
window.NotificationSounds = NotificationSounds;
