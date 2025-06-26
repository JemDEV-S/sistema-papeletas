<!-- Notificaciones dropdown -->
<div class="relative" x-data="{ open: false, notifications: [] }" x-init="
    async function fetchNotifications() {
        try {
            const response = await fetch('/api/notifications/unread');
            if (response.ok) {
                const data = await response.json();
                notifications = data.slice(0, 5); // Mostrar solo las últimas 5
            }
        } catch (error) {
            console.error('Error fetching notifications:', error);
        }
    }
    fetchNotifications();
    setInterval(fetchNotifications, 30000); // Actualizar cada 30 segundos
">
    <button @click="open = !open" 
            class="relative p-2 text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 rounded-full">
        <span class="sr-only">Ver notificaciones</span>
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                  d="M15 17h5l-5-5 5-5H9.588l4.5 5-4.5 5z"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                  d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2v10z"></path>
        </svg>
        
        <!-- Badge de contador -->
        <span x-show="$store.notifications.count > 0" 
              x-text="$store.notifications.count > 99 ? '99+' : $store.notifications.count"
              class="absolute -top-1 -right-1 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-600 rounded-full min-w-[1.25rem]"></span>
    </button>

    <!-- Dropdown de notificaciones -->
    <div x-show="open" 
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="transform opacity-0 scale-95"
         x-transition:enter-end="transform opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="transform opacity-100 scale-100"
         x-transition:leave-end="transform opacity-0 scale-95"
         @click.away="open = false"
         class="absolute right-0 z-50 w-80 mt-2 bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
         style="top: 100%;">
        
        <!-- Header -->
        <div class="px-4 py-3 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-medium text-gray-900">Notificaciones</h3>
                <button @click="markAllAsRead()" 
                        x-show="notifications.length > 0"
                        class="text-xs text-blue-600 hover:text-blue-800 focus:outline-none">
                    Marcar todas como leídas
                </button>
            </div>
        </div>

        <!-- Lista de notificaciones -->
        <div class="max-h-96 overflow-y-auto">
            <template x-if="notifications.length === 0">
                <div class="px-4 py-6 text-center">
                    <svg class="w-8 h-8 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                    </svg>
                    <p class="mt-2 text-sm text-gray-500">No hay notificaciones nuevas</p>
                </div>
            </template>

            <template x-for="notification in notifications" :key="notification.id">
                <div class="px-4 py-3 border-b border-gray-100 hover:bg-gray-50 cursor-pointer"
                     @click="markAsRead(notification.id)">
                    <div class="flex items-start">
                        <!-- Icono de notificación -->
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center"
                                 :class="{
                                     'bg-blue-100': notification.data?.category === 'permission_request',
                                     'bg-green-100': notification.data?.category === 'approval',
                                     'bg-red-100': notification.data?.category === 'rejection',
                                     'bg-yellow-100': notification.data?.category === 'reminder',
                                     'bg-gray-100': !notification.data?.category
                                 }">
                                <svg class="w-4 h-4" 
                                     :class="{
                                         'text-blue-600': notification.data?.category === 'permission_request',
                                         'text-green-600': notification.data?.category === 'approval',
                                         'text-red-600': notification.data?.category === 'rejection',
                                         'text-yellow-600': notification.data?.category === 'reminder',
                                         'text-gray-600': !notification.data?.category
                                     }"
                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <template x-if="notification.data?.category === 'permission_request'">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </template>
                                    <template x-if="notification.data?.category === 'approval'">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                              d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </template>
                                    <template x-if="notification.data?.category === 'rejection'">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                              d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </template>
                                    <template x-if="notification.data?.category === 'reminder'">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                              d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </template>
                                    <template x-if="!notification.data?.category">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                              d="M15 17h5l-5-5 5-5H9.588l4.5 5-4.5 5z"></path>
                                    </template>
                                </svg>
                            </div>
                        </div>

                        <!-- Contenido de la notificación -->
                        <div class="ml-3 flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900" x-text="notification.title"></p>
                            <p class="text-sm text-gray-500 line-clamp-2" x-text="notification.message"></p>
                            <p class="text-xs text-gray-400 mt-1" x-text="formatTime(notification.created_at)"></p>
                        </div>

                        <!-- Indicador de no leída -->
                        <div class="flex-shrink-0">
                            <div class="w-2 h-2 bg-blue-600 rounded-full"></div>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Footer -->
        <div class="px-4 py-3 border-t border-gray-200">
            <a href="{{ route('notifications.index') }}" 
               class="block text-sm text-center text-blue-600 hover:text-blue-800 font-medium">
                Ver todas las notificaciones
            </a>
        </div>
    </div>

    <script>
        function markAsRead(notificationId) {
            fetch(`/notifications/${notificationId}/mark-read`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json',
                },
            })
            .then(response => {
                if (response.ok) {
                    // Remover de la lista local
                    this.notifications = this.notifications.filter(n => n.id !== notificationId);
                    // Actualizar contador global
                    Alpine.store('notifications').markAsRead(notificationId);
                }
            })
            .catch(error => console.error('Error marking notification as read:', error));
        }

        function markAllAsRead() {
            fetch('/notifications/mark-all-read', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json',
                },
            })
            .then(response => {
                if (response.ok) {
                    this.notifications = [];
                    Alpine.store('notifications').count = 0;
                }
            })
            .catch(error => console.error('Error marking all notifications as read:', error));
        }

        function formatTime(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const diffMs = now - date;
            const diffMins = Math.floor(diffMs / 60000);
            const diffHours = Math.floor(diffMs / 3600000);
            const diffDays = Math.floor(diffMs / 86400000);

            if (diffMins < 1) return 'Ahora';
            if (diffMins < 60) return `${diffMins} min`;
            if (diffHours < 24) return `${diffHours}h`;
            if (diffDays < 7) return `${diffDays}d`;
            
            return date.toLocaleDateString('es-PE', { 
                month: 'short', 
                day: 'numeric' 
            });
        }
    </script>
</div>