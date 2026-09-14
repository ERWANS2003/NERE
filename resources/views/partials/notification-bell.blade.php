<div x-data="notificationBell()" x-init="pollNotifications()" class="relative">
    <!-- Notification Bell -->
    <button @click="togglePanel()" class="relative p-2 text-gray-400 hover:text-white transition">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
        </svg>
        <!-- Badge -->
        <template x-if="unreadCount > 0">
            <span class="absolute top-1 right-1 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-600 rounded-full"
                  x-text="unreadCount > 99 ? '99+' : unreadCount"></span>
        </template>
    </button>

    <!-- Notification Panel -->
    <template x-if="showPanel">
        <div class="absolute right-0 mt-2 w-96 bg-dark-800 rounded-xl border border-dark-700 shadow-2xl z-50">
            <!-- Header -->
            <div class="px-6 py-4 border-b border-dark-700 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-white">Notifications</h3>
                <button @click="togglePanel()" class="text-gray-400 hover:text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Notifications List -->
            <div class="max-h-96 overflow-y-auto">
                <template x-if="notifications.length === 0">
                    <div class="px-6 py-12 text-center text-gray-500">
                        <svg class="w-12 h-12 mx-auto mb-3 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                        </svg>
                        <p>Aucune notification</p>
                    </div>
                </template>

                <template x-if="notifications.length > 0">
                    <div class="divide-y divide-dark-700">
                        <template x-for="(notification, index) in notifications" :key="index">
                            <div class="px-6 py-4 hover:bg-dark-700/30 transition cursor-pointer"
                                 @click="goToNotification(notification)">
                                <div class="flex gap-3">
                                    <!-- Icon -->
                                    <div class="flex-shrink-0 text-xl" x-text="notification.icon"></div>
                                    
                                    <!-- Content -->
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-semibold text-white" x-text="notification.title"></p>
                                        <p class="text-xs text-gray-400 mt-1 line-clamp-2" x-text="notification.message"></p>
                                        <p class="text-xs text-gray-500 mt-2" x-text="notification.created_at"></p>
                                    </div>

                                    <!-- Close Button -->
                                    <button @click.stop="deleteNotification(notification.id)"
                                            class="text-gray-500 hover:text-red-400 transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>
            </div>

            <!-- Footer -->
            <div class="px-6 py-3 border-t border-dark-700 bg-dark-900/50 flex gap-2">
                <button @click="markAllAsRead()" class="flex-1 px-3 py-2 text-sm bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition">
                    Marquer comme lu
                </button>
                <button @click="deleteAll()" class="flex-1 px-3 py-2 text-sm bg-red-600 hover:bg-red-700 text-white rounded-lg transition">
                    Effacer tout
                </button>
            </div>
        </div>
    </template>
</div>

<script>
function notificationBell() {
    return {
        showPanel: false,
        notifications: [],
        unreadCount: 0,
        pollInterval: null,

        togglePanel() {
            this.showPanel = !this.showPanel;
        },

        async pollNotifications() {
            // Poll every 5 seconds
            this.pollInterval = setInterval(async () => {
                try {
                    const response = await fetch('/api/dashboard-notifications', {
                        headers: {
                            'Authorization': `Bearer ${document.querySelector('meta[name="api-token"]')?.content || ''}`,
                            'Accept': 'application/json',
                        }
                    });

                    if (response.ok) {
                        const data = await response.json();
                        this.notifications = data.notifications;
                        this.unreadCount = data.unread_count;

                        // Auto-hide panel after 5 seconds if no notifications
                        if (this.unreadCount === 0 && this.showPanel) {
                            setTimeout(() => this.showPanel = false, 5000);
                        }
                    }
                } catch (error) {
                    console.error('Failed to fetch notifications:', error);
                }
            }, 5000); // Poll every 5 seconds

            // Initial fetch
            await this.fetchNotifications();
        },

        async fetchNotifications() {
            try {
                const response = await fetch('/api/dashboard-notifications', {
                    headers: {
                        'Authorization': `Bearer ${document.querySelector('meta[name="api-token"]')?.content || ''}`,
                        'Accept': 'application/json',
                    }
                });

                if (response.ok) {
                    const data = await response.json();
                    this.notifications = data.notifications;
                    this.unreadCount = data.unread_count;
                }
            } catch (error) {
                console.error('Failed to fetch notifications:', error);
            }
        },

        async markAsRead(notificationId) {
            try {
                await fetch(`/api/dashboard-notifications/${notificationId}/read`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${document.querySelector('meta[name="api-token"]')?.content || ''}`,
                        'Content-Type': 'application/json',
                    }
                });
                await this.fetchNotifications();
            } catch (error) {
                console.error('Failed to mark as read:', error);
            }
        },

        async markAllAsRead() {
            try {
                await fetch('/api/dashboard-notifications/read-all', {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${document.querySelector('meta[name="api-token"]')?.content || ''}`,
                        'Content-Type': 'application/json',
                    }
                });
                await this.fetchNotifications();
            } catch (error) {
                console.error('Failed to mark all as read:', error);
            }
        },

        async deleteNotification(notificationId) {
            try {
                await fetch(`/api/dashboard-notifications/${notificationId}`, {
                    method: 'DELETE',
                    headers: {
                        'Authorization': `Bearer ${document.querySelector('meta[name="api-token"]')?.content || ''}`,
                        'Content-Type': 'application/json',
                    }
                });
                await this.fetchNotifications();
            } catch (error) {
                console.error('Failed to delete notification:', error);
            }
        },

        async deleteAll() {
            if (!confirm('Êtes-vous sûr?')) return;
            try {
                await fetch('/api/dashboard-notifications', {
                    method: 'DELETE',
                    headers: {
                        'Authorization': `Bearer ${document.querySelector('meta[name="api-token"]')?.content || ''}`,
                        'Content-Type': 'application/json',
                    }
                });
                await this.fetchNotifications();
            } catch (error) {
                console.error('Failed to delete all notifications:', error);
            }
        },

        goToNotification(notification) {
            if (notification.action_url) {
                window.location.href = notification.action_url;
            }
        },

        destroy() {
            if (this.pollInterval) {
                clearInterval(this.pollInterval);
            }
        }
    };
}
</script>
