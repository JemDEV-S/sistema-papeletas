<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Sistema de Papeletas Digitales') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- Chart.js para gráficos -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    @stack('styles')
</head>
<body class="font-sans antialiased bg-gray-50">
    <div class="min-h-screen flex" x-data="{ sidebarOpen: false }">
        <!-- Sidebar móvil overlay -->
        <div x-show="sidebarOpen" class="fixed inset-0 z-40 lg:hidden">
            <div x-show="sidebarOpen" 
                 x-transition:enter="transition-opacity ease-linear duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition-opacity ease-linear duration-300"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-gray-600 bg-opacity-75"
                 @click="sidebarOpen = false"></div>

            <!-- Sidebar móvil -->
            <div x-show="sidebarOpen" 
                 x-transition:enter="transition ease-in-out duration-300 transform"
                 x-transition:enter-start="-translate-x-full"
                 x-transition:enter-end="translate-x-0"
                 x-transition:leave="transition ease-in-out duration-300 transform"
                 x-transition:leave-start="translate-x-0"
                 x-transition:leave-end="-translate-x-full"
                 class="relative flex flex-col w-64 max-w-xs bg-white border-r border-gray-200">
                @include('layouts.sidebar')
            </div>
        </div>

        <!-- Sidebar desktop -->
        <div class="hidden lg:flex lg:flex-shrink-0">
            <div class="flex flex-col w-64 bg-white border-r border-gray-200">
                @include('layouts.sidebar')
            </div>
        </div>

        <!-- Contenido principal -->
        <div class="flex flex-col flex-1 w-0">
            <!-- Header -->
            <header class="relative flex items-center justify-between h-16 px-4 bg-white border-b border-gray-200 sm:px-6 lg:px-8">
                <!-- Botón menú móvil -->
                <button @click="sidebarOpen = true" class="lg:hidden">
                    <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>

                <!-- Título de página -->
                <div class="flex-1 lg:flex-none">
                    <h1 class="text-xl font-semibold text-gray-900">
                        @yield('title', 'Dashboard')
                    </h1>
                </div>

                <!-- Notificaciones y usuario -->
                <div class="flex items-center space-x-4">
                    @include('layouts.notifications')
                    @include('layouts.user-dropdown')
                </div>
            </header>

            <!-- Breadcrumbs -->
            @if(isset($breadcrumbs) && count($breadcrumbs) > 0)
            <nav class="flex px-4 py-3 bg-gray-50 sm:px-6 lg:px-8" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    @foreach($breadcrumbs as $breadcrumb)
                    <li class="inline-flex items-center">
                        @if(!$loop->last)
                            <a href="{{ $breadcrumb['url'] }}" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600">
                                {{ $breadcrumb['title'] }}
                            </a>
                            <svg class="w-6 h-6 text-gray-400 ml-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                            </svg>
                        @else
                            <span class="text-sm font-medium text-gray-500">{{ $breadcrumb['title'] }}</span>
                        @endif
                    </li>
                    @endforeach
                </ol>
            </nav>
            @endif

            <!-- Alertas -->
            @if(session('success') || session('error') || session('warning') || session('info'))
            <div class="px-4 py-3 sm:px-6 lg:px-8">
                @if(session('success'))
                <div class="p-4 rounded-md bg-green-50 mb-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="w-5 h-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                        </div>
                    </div>
                </div>
                @endif

                @if(session('error'))
                <div class="p-4 rounded-md bg-red-50 mb-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="w-5 h-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
                        </div>
                    </div>
                </div>
                @endif

                @if(session('warning'))
                <div class="p-4 rounded-md bg-yellow-50 mb-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="w-5 h-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-yellow-800">{{ session('warning') }}</p>
                        </div>
                    </div>
                </div>
                @endif

                @if(session('info'))
                <div class="p-4 rounded-md bg-blue-50 mb-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="w-5 h-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-blue-800">{{ session('info') }}</p>
                        </div>
                    </div>
                </div>
                @endif
            </div>
            @endif

            <!-- Contenido -->
            <main class="flex-1 px-4 py-6 sm:px-6 lg:px-8 overflow-y-auto">
                @yield('content')
            </main>
        </div>
    </div>

    @stack('scripts')
    
    <!-- Script global para notificaciones -->
    <script>
        // Configuración global de Alpine.js
        document.addEventListener('alpine:init', () => {
            Alpine.store('notifications', {
                count: 0,
                items: [],
                
                async fetchCount() {
                    try {
                        const response = await fetch('{{ route("notifications.api.unread-count") }}');
                        if (response.ok) {
                            const data = await response.json();
                            this.count = data.count;
                        }
                    } catch (error) {
                        console.error('Error fetching notification count:', error);
                    }
                },
                
                markAsRead(id) {
                    this.items = this.items.filter(item => item.id !== id);
                    this.count = Math.max(0, this.count - 1);
                }
            });
        });

        // Actualizar contador de notificaciones cada 30 segundos
        setInterval(() => {
            if (Alpine.store('notifications')) {
                Alpine.store('notifications').fetchCount();
            }
        }, 30000);

        // Inicializar contador al cargar la página
        document.addEventListener('DOMContentLoaded', () => {
            if (Alpine.store('notifications')) {
                Alpine.store('notifications').fetchCount();
            }
        });
    </script>
</body>
</html>