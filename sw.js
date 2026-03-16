// Service Worker for background notifications and real-time updates
const CACHE_NAME = 'it-service-request-v1';
const urlsToCache = [
    '/',
    '/assets/css/style.css',
    '/assets/js/app.js',
    '/assets/js/advanced-notifications.js',
    '/assets/sounds/notification-sounds.js',
    '/assets/images/favicon.png'
];

// Install event - cache resources
self.addEventListener('install', event => {
    console.log('Service Worker: Installing...');
    
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                console.log('Service Worker: Caching app shell');
                return cache.addAll(urlsToCache);
            })
            .then(() => {
                console.log('Service Worker: Installation complete');
                return self.skipWaiting();
            })
    );
});

// Activate event - clean up old caches
self.addEventListener('activate', event => {
    console.log('Service Worker: Activating...');
    
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cacheName => {
                    if (cacheName !== CACHE_NAME) {
                        console.log('Service Worker: Deleting old cache:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        }).then(() => {
            console.log('Service Worker: Activation complete');
        })
    );
});

// Fetch event - serve from cache, fallback to network
self.addEventListener('fetch', event => {
    event.respondWith(
        caches.match(event.request)
            .then(response => {
                // Return cached version or fetch from network
                if (response) {
                    console.log('Service Worker: Serving from cache:', event.request.url);
                    return response;
                }
                
                console.log('Service Worker: Fetching from network:', event.request.url);
                return fetch(event.request);
            })
            .catch(error => {
                console.error('Service Worker: Fetch failed:', error);
            })
    );
});

// Push event - handle push notifications
self.addEventListener('push', event => {
    console.log('Service Worker: Push message received');
    
    if (!event.data) {
        console.log('Service Worker: Push message has no data');
        return;
    }
    
    const data = event.data.json();
    const { title, message, type, relatedId, relatedType } = data;
    
    const options = {
        body: message,
        icon: '/assets/images/favicon.png',
        badge: '/assets/images/badge.png',
        vibrate: [200, 100, 200],
        data: {
            relatedId: relatedId,
            relatedType: relatedType,
            url: relatedId ? `/request-detail.html?id=${relatedId}` : '/'
        },
        actions: [
            {
                action: 'view',
                title: 'Xem chi tiết'
            },
            {
                action: 'dismiss',
                title: 'Đóng'
            }
        ]
    };
    
    // Customize based on notification type
    switch (type) {
        case 'success':
            options.icon = '/assets/images/success-icon.png';
            options.vibrate = [100, 50, 100];
            break;
        case 'warning':
            options.icon = '/assets/images/warning-icon.png';
            options.vibrate = [300, 100, 300];
            break;
        case 'error':
            options.icon = '/assets/images/error-icon.png';
            options.vibrate = [400, 200, 400];
            break;
    }
    
    event.waitUntil(
        self.registration.showNotification(title, options)
    );
});

// Notification click event
self.addEventListener('notificationclick', event => {
    console.log('Service Worker: Notification clicked');
    
    event.notification.close();
    
    if (event.action === 'dismiss') {
        return;
    }
    
    // Handle notification click
    const urlToOpen = event.notification.data.url || '/';
    
    event.waitUntil(
        clients.matchAll({
            type: 'window',
            includeUncontrolled: true
        }).then(clientList => {
            // Focus existing window or open new one
            for (const client of clientList) {
                if (client.url === urlToOpen && 'focus' in client) {
                    return client.focus();
                }
            }
            
            if (clients.openWindow) {
                return clients.openWindow(urlToOpen);
            }
        })
    );
});

// Background sync for offline actions
self.addEventListener('sync', event => {
    console.log('Service Worker: Background sync triggered');
    
    if (event.tag === 'background-sync-notifications') {
        event.waitUntil(
            // Sync any pending notifications
            syncPendingNotifications()
        );
    }
});

// Message event - handle messages from main app
self.addEventListener('message', event => {
    console.log('Service Worker: Message received:', event.data);
    
    const { type, data } = event.data;
    
    switch (type) {
        case 'CACHE_UPDATED':
            // Update cache with new data
            updateCache(data);
            break;
        case 'SHOW_NOTIFICATION':
            // Show notification from main app
            showNotification(data);
            break;
    }
});

// Helper functions
async function syncPendingNotifications() {
    try {
        // Get pending notifications from IndexedDB
        const pending = await getPendingNotifications();
        
        for (const notification of pending) {
            try {
                // Send to server
                await fetch('/api/notifications.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(notification)
                });
                
                // Remove from pending
                await removePendingNotification(notification.id);
            } catch (error) {
                console.error('Failed to sync notification:', error);
            }
        }
    } catch (error) {
        console.error('Background sync failed:', error);
    }
}

function updateCache(data) {
    return caches.open(CACHE_NAME).then(cache => {
        // Update specific cache entries
        if (data.url && data.response) {
            cache.put(data.url, data.response);
        }
    });
}

function showNotification(data) {
    if (self.registration && self.registration.showNotification) {
        const options = {
            body: data.message,
            icon: '/assets/images/favicon.png',
            tag: `manual-${Date.now()}`
        };
        
        self.registration.showNotification(data.title, options);
    }
}

// IndexedDB helpers for offline storage
function getPendingNotifications() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open('ITServiceRequestDB', 1);
        
        request.onerror = () => reject(request.error);
        request.onsuccess = () => {
            const db = request.result;
            const transaction = db.transaction(['pendingNotifications'], 'readonly');
            const store = transaction.objectStore('pendingNotifications');
            const getRequest = store.getAll();
            
            getRequest.onsuccess = () => resolve(getRequest.result);
            getRequest.onerror = () => reject(getRequest.error);
        };
        
        request.onupgradeneeded = (event) => {
            const db = event.target.result;
            if (!db.objectStoreNames.contains('pendingNotifications')) {
                db.createObjectStore('pendingNotifications', { keyPath: 'id' });
            }
        };
    });
}

function removePendingNotification(id) {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open('ITServiceRequestDB', 1);
        
        request.onerror = () => reject(request.error);
        request.onsuccess = () => {
            const db = request.result;
            const transaction = db.transaction(['pendingNotifications'], 'readwrite');
            const store = transaction.objectStore('pendingNotifications');
            const deleteRequest = store.delete(id);
            
            deleteRequest.onsuccess = () => resolve();
            deleteRequest.onerror = () => reject(deleteRequest.error);
        };
    });
}

// Periodic background sync
self.addEventListener('periodicsync', event => {
    console.log('Service Worker: Periodic sync triggered');
    
    if (event.tag === 'periodic-notification-check') {
        event.waitUntil(
            // Check for new notifications periodically
            checkForNewNotifications()
        );
    }
});

async function checkForNewNotifications() {
    try {
        // This would typically check with server for new notifications
        // For now, just log the attempt
        console.log('Service Worker: Checking for new notifications...');
    } catch (error) {
        console.error('Periodic notification check failed:', error);
    }
}
