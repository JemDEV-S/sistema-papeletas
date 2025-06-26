<div class="flex flex-col h-full">
    <!-- Logo -->
    <div class="flex items-center justify-center h-16 px-4 border-b border-gray-200">
        <div class="text-center">
            <h2 class="text-lg font-bold text-blue-600">Sistema de</h2>
            <p class="text-sm text-gray-600">Papeletas Digitales</p>
        </div>
    </div>

    <!-- Navegación -->
    <nav class="flex-1 px-4 py-6 space-y-1 overflow-y-auto">
        <!-- Dashboard -->
        <a href="{{ route('dashboard') }}" 
           class="flex items-center px-2 py-2 text-sm font-medium rounded-md {{ request()->routeIs('dashboard') ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v6H8V5z"></path>
            </svg>
            Dashboard
        </a>

        <!-- Mis Solicitudes / Solicitudes -->
        @if(auth()->user()->hasPermission('create_permission_request'))
        <div x-data="{ open: {{ request()->routeIs('permissions.*') ? 'true' : 'false' }} }">
            <button @click="open = !open" 
                    class="flex items-center justify-between w-full px-2 py-2 text-sm font-medium text-left rounded-md {{ request()->routeIs('permissions.*') ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Permisos
                </div>
                <svg class="w-5 h-5 transition-transform" :class="{ 'rotate-90': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </button>
            <div x-show="open" x-transition class="mt-1 space-y-1">
                <a href="{{ route('permissions.index') }}" 
                   class="flex items-center px-4 py-2 ml-6 text-sm rounded-md {{ request()->routeIs('permissions.index') ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                    Mis Solicitudes
                </a>
                <a href="{{ route('permissions.create') }}" 
                   class="flex items-center px-4 py-2 ml-6 text-sm rounded-md {{ request()->routeIs('permissions.create') ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                    Nueva Solicitud
                </a>
            </div>
        </div>
        @endif

        <!-- Aprobaciones -->
        @if(auth()->user()->hasPermission('approve_level_1') || auth()->user()->hasPermission('approve_level_2'))
        <a href="{{ route('approvals.index') }}" 
           class="flex items-center px-2 py-2 text-sm font-medium rounded-md {{ request()->routeIs('approvals.*') ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}"
           x-data="{ pendingCount: 0 }"
           x-init="
               async function fetchCount() {
                   try {
                       const response = await fetch('{{ route("approvals.api.pending-count") }}');
                       const data = await response.json();
                       pendingCount = data.count;
                   } catch (error) {
                       console.error('Error fetching pending approvals:', error);
                   }
               }
               fetchCount();
               setInterval(fetchCount, 60000);
           ">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            Aprobaciones
            <span x-show="pendingCount > 0" 
                  x-text="pendingCount"
                  class="inline-flex items-center justify-center w-5 h-5 ml-2 text-xs font-bold text-white bg-red-500 rounded-full"></span>
        </a>
        @endif

        <!-- Sistema Biométrico -->
        <a href="{{ route('biometric.index') }}" 
           class="flex items-center px-2 py-2 text-sm font-medium rounded-md {{ request()->routeIs('biometric.*') ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 008 4.07M3 15.364c.64-1.319 1-2.8 1-4.364 0-1.457.39-2.823 1.07-4"></path>
            </svg>
            Control Biométrico
        </a>

        <!-- Reportes -->
        @if(auth()->user()->hasPermission('view_reports') || auth()->user()->hasPermission('view_department_reports'))
        <div x-data="{ open: {{ request()->routeIs('reports.*') ? 'true' : 'false' }} }">
            <button @click="open = !open" 
                    class="flex items-center justify-between w-full px-2 py-2 text-sm font-medium text-left rounded-md {{ request()->routeIs('reports.*') ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    Reportes
                </div>
                <svg class="w-5 h-5 transition-transform" :class="{ 'rotate-90': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </button>
            <div x-show="open" x-transition class="mt-1 space-y-1">
                <a href="{{ route('reports.permissions') }}" 
                   class="flex items-center px-4 py-2 ml-6 text-sm rounded-md {{ request()->routeIs('reports.permissions') ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                    Reporte de Permisos
                </a>
                <a href="{{ route('reports.usage') }}" 
                   class="flex items-center px-4 py-2 ml-6 text-sm rounded-md {{ request()->routeIs('reports.usage') ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                    Uso de Permisos
                </a>
                <a href="{{ route('reports.balances') }}" 
                   class="flex items-center px-4 py-2 ml-6 text-sm rounded-md {{ request()->routeIs('reports.balances') ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                    Saldos de Permisos
                </a>
                @if(auth()->user()->hasPermission('view_department_reports'))
                <a href="{{ route('reports.department') }}" 
                   class="flex items-center px-4 py-2 ml-6 text-sm rounded-md {{ request()->routeIs('reports.department') ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                    Por Departamento
                </a>
                @endif
            </div>
        </div>
        @endif

        <!-- Administración -->
        @if(auth()->user()->hasPermission('manage_users') || auth()->user()->hasPermission('manage_system_config'))
        <div class="pt-4 mt-4 border-t border-gray-200">
            <p class="px-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">Administración</p>
            
            @if(auth()->user()->hasPermission('manage_users'))
            <a href="{{ route('users.index') }}" 
               class="flex items-center px-2 py-2 mt-2 text-sm font-medium rounded-md {{ request()->routeIs('users.*') ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                </svg>
                Usuarios
            </a>
            @endif

            @if(auth()->user()->hasPermission('manage_system_config'))
            <div x-data="{ open: {{ request()->routeIs('system.*') ? 'true' : 'false' }} }">
                <button @click="open = !open" 
                        class="flex items-center justify-between w-full px-2 py-2 text-sm font-medium text-left rounded-md {{ request()->routeIs('system.*') ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        Sistema
                    </div>
                    <svg class="w-5 h-5 transition-transform" :class="{ 'rotate-90': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </button>
                <div x-show="open" x-transition class="mt-1 space-y-1">
                    <a href="{{ route('system.settings') }}" 
                       class="flex items-center px-4 py-2 ml-6 text-sm rounded-md {{ request()->routeIs('system.settings') ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                        Configuración
                    </a>
                    <a href="{{ route('system.permission-types') }}" 
                       class="flex items-center px-4 py-2 ml-6 text-sm rounded-md {{ request()->routeIs('system.permission-types') ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                        Tipos de Permiso
                    </a>
                    <a href="{{ route('system.departments') }}" 
                       class="flex items-center px-4 py-2 ml-6 text-sm rounded-md {{ request()->routeIs('system.departments') ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                        Departamentos
                    </a>
                    <a href="{{ route('system.audit-logs') }}" 
                       class="flex items-center px-4 py-2 ml-6 text-sm rounded-md {{ request()->routeIs('system.audit-logs') ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                        Logs de Auditoría
                    </a>
                </div>
            </div>
            @endif
        </div>
        @endif
    </nav>

    <!-- Información del usuario -->
    <div class="px-4 py-4 border-t border-gray-200">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                    <span class="text-sm font-medium text-white">
                        {{ substr(auth()->user()->first_name, 0, 1) }}{{ substr(auth()->user()->last_name, 0, 1) }}
                    </span>
                </div>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium text-gray-700">{{ auth()->user()->full_name }}</p>
                <p class="text-xs text-gray-500">{{ auth()->user()->role->name }}</p>
            </div>
        </div>
    </div>
</div>