<!-- User dropdown -->
<div class="relative" x-data="{ open: false }">
    <button @click="open = !open" 
            class="flex items-center max-w-xs text-sm bg-white rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            id="user-menu-button" 
            aria-expanded="false" 
            aria-haspopup="true">
        <span class="sr-only">Abrir menú de usuario</span>
        <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-blue-600 rounded-full flex items-center justify-center">
            <span class="text-sm font-medium text-white">
                {{ substr(auth()->user()->first_name, 0, 1) }}{{ substr(auth()->user()->last_name, 0, 1) }}
            </span>
        </div>
        <div class="hidden ml-3 sm:block">
            <div class="text-left">
                <p class="text-sm font-medium text-gray-700">{{ auth()->user()->full_name }}</p>
                <p class="text-xs text-gray-500">{{ auth()->user()->role->name }}</p>
            </div>
        </div>
        <svg class="hidden ml-2 h-5 w-5 text-gray-400 sm:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
        </svg>
    </button>

    <!-- Dropdown menu -->
    <div x-show="open" 
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="transform opacity-0 scale-95"
         x-transition:enter-end="transform opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="transform opacity-100 scale-100"
         x-transition:leave-end="transform opacity-0 scale-95"
         @click.away="open = false"
         class="absolute right-0 z-50 w-64 mt-2 origin-top-right bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
         role="menu" 
         aria-orientation="vertical" 
         aria-labelledby="user-menu-button">
        
        <!-- Header del dropdown -->
        <div class="px-4 py-3 border-b border-gray-200">
            <div class="flex items-center">
                <div class="w-10 h-10 bg-gradient-to-r from-blue-500 to-blue-600 rounded-full flex items-center justify-center">
                    <span class="text-sm font-medium text-white">
                        {{ substr(auth()->user()->first_name, 0, 1) }}{{ substr(auth()->user()->last_name, 0, 1) }}
                    </span>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-900">{{ auth()->user()->full_name }}</p>
                    <p class="text-xs text-gray-500">{{ auth()->user()->email }}</p>
                    <p class="text-xs text-blue-600 font-medium">{{ auth()->user()->role->name }}</p>
                </div>
            </div>
        </div>

        <!-- Información adicional -->
        <div class="px-4 py-2 bg-gray-50">
            <div class="grid grid-cols-2 gap-2 text-xs">
                <div>
                    <span class="text-gray-500">DNI:</span>
                    <span class="font-medium text-gray-900">{{ auth()->user()->dni }}</span>
                </div>
                <div>
                    <span class="text-gray-500">Depto:</span>
                    <span class="font-medium text-gray-900">{{ auth()->user()->department->code ?? 'N/A' }}</span>
                </div>
            </div>
        </div>

        <!-- Opciones del menú -->
        <div class="py-1" role="none">
            <!-- Mi Perfil -->
            <a href="{{ route('profile.edit') }}" 
               class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900"
               role="menuitem">
                <svg class="w-4 h-4 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
                Mi Perfil
            </a>

            <!-- Mis Notificaciones -->
            <a href="{{ route('notifications.index') }}" 
               class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900"
               role="menuitem">
                <svg class="w-4 h-4 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5-5 5-5H9.588l4.5 5-4.5 5z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2v10z"></path>
                </svg>
                Notificaciones
                <span x-show="$store.notifications.count > 0" 
                      x-text="$store.notifications.count"
                      class="ml-auto inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white bg-red-600 rounded-full"></span>
            </a>

            <!-- Historial Biométrico -->
            <a href="{{ route('biometric.history') }}" 
               class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900"
               role="menuitem">
                <svg class="w-4 h-4 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Mi Historial
            </a>

            <!-- Separador -->
            <div class="border-t border-gray-100"></div>

            <!-- Configuración (solo si tiene permisos) -->
            @if(auth()->user()->hasPermission('manage_system_config'))
            <a href="{{ route('system.settings') }}" 
               class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900"
               role="menuitem">
                <svg class="w-4 h-4 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                Configuración
            </a>
            @endif

            <!-- Ayuda -->
            <a href="#" 
               @click.prevent="showHelp()"
               class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900"
               role="menuitem">
                <svg class="w-4 h-4 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Ayuda
            </a>

            <!-- Separador -->
            <div class="border-t border-gray-100"></div>

            <!-- Cerrar Sesión -->
            <form method="POST" action="{{ route('logout') }}" class="block">
                @csrf
                <button type="submit" 
                        class="flex items-center w-full px-4 py-2 text-sm text-red-700 hover:bg-red-50 hover:text-red-900"
                        role="menuitem">
                    <svg class="w-4 h-4 mr-3 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                    </svg>
                    Cerrar Sesión
                </button>
            </form>
        </div>

        <!-- Footer con información de la sesión -->
        <div class="px-4 py-2 bg-gray-50 border-t border-gray-100">
            <div class="flex items-center justify-between text-xs text-gray-500">
                <span>Última conexión:</span>
                <span>{{ auth()->user()->updated_at->diffForHumans() }}</span>
            </div>
        </div>
    </div>

    <!-- Modal de ayuda -->
    <div x-data="{ showHelpModal: false }" 
         @show-help.window="showHelpModal = true">
        <div x-show="showHelpModal" 
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-50 overflow-y-auto"
             style="display: none;">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
                
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                <h3 class="text-lg leading-6 font-medium text-gray-900">Ayuda del Sistema</h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500">
                                        Para obtener ayuda sobre el uso del sistema, contacte al área de Sistemas o RRHH.
                                    </p>
                                    <div class="mt-3 space-y-2">
                                        <p class="text-xs text-gray-600"><strong>Email:</strong> sistemas@sanjeronimo.gob.pe</p>
                                        <p class="text-xs text-gray-600"><strong>Teléfono:</strong> (084) 123-4567</p>
                                        <p class="text-xs text-gray-600"><strong>Horario:</strong> L-V 8:00 AM - 5:00 PM</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button @click="showHelpModal = false" 
                                type="button" 
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Entendido
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showHelp() {
            window.dispatchEvent(new CustomEvent('show-help'));
        }
    </script>
</div>