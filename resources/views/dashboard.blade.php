<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>{{ __('Dashboard') }} - Vespot HCE</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    
    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        body { font-family: 'Figtree', sans-serif; }
    </style>
</head>
<body class="min-h-screen bg-gray-100 dark:bg-gray-900">
    <div class="min-h-screen">
        <!-- Header -->
        <header class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <!-- Logo -->
                    <div class="flex items-center">
                        <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        <span class="ml-2 text-xl font-bold text-gray-800 dark:text-white">Vespot HCE</span>
                    </div>
                    
                    <!-- User Menu -->
                    <div class="flex items-center space-x-4">
                        <span class="text-sm text-gray-600 dark:text-gray-300">
                            {{ auth()->user()->name ?? auth()->user()->email }}
                        </span>
                        <span class="px-2 py-1 text-xs font-medium rounded-full 
                            {{ auth()->user()->hasRole('admin') ? 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200' : 
                               (auth()->user()->hasRole('doctor') ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : 
                               (auth()->user()->hasRole('nurse') ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 
                               'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200')) }}">
                            {{ auth()->user()->roles->first()->name ?? 'Usuario' }}
                        </span>
                        
                        <livewire:auth.logout />
                    </div>
                </div>
            </div>
        </header>
        
        <!-- Main Content -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-8">
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white mb-4">
                    {{ __('Bienvenido a Vespot HCE') }}
                </h1>
                <p class="text-gray-600 dark:text-gray-400 mb-6">
                    {{ __('Sistema de Historias Clínicas Electrónicas') }}
                </p>
                
                <!-- Quick Stats Placeholder -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-blue-100 dark:bg-blue-900 mr-4">
                                <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm text-blue-600 dark:text-blue-400 font-medium">{{ __('Pacientes') }}</p>
                                <p class="text-2xl font-bold text-blue-800 dark:text-blue-200">--</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-green-100 dark:bg-green-900 mr-4">
                                <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm text-green-600 dark:text-green-400 font-medium">{{ __('Consultas Hoy') }}</p>
                                <p class="text-2xl font-bold text-green-800 dark:text-green-200">--</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-purple-100 dark:bg-purple-900 mr-4">
                                <svg class="h-6 w-6 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm text-purple-600 dark:text-purple-400 font-medium">{{ __('Laboratorios Pendientes') }}</p>
                                <p class="text-2xl font-bold text-purple-800 dark:text-purple-200">--</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Placeholder for upcoming features -->
                <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                    <p class="text-center text-gray-500 dark:text-gray-400 text-sm">
                        {{ __('Más funciones próximamente...') }}
                    </p>
                </div>
            </div>
        </main>
    </div>
    
    @stack('scripts')
</body>
</html>