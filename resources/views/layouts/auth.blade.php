<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>@yield('title', 'Vespot HCE - Iniciar Sesión')</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    
    <!-- Styles -->
    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        body {
            font-family: 'Figtree', sans-serif;
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-indigo-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900">
    <div class="min-h-screen flex flex-col items-center justify-center p-4">
        <!-- Logo & Brand -->
        <div class="mb-8 text-center">
            <div class="flex items-center justify-center mb-4">
                <svg class="w-12 h-12 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Vespot HCE</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Historias Clínicas Electrónicas</p>
        </div>
        
        <!-- Main Content -->
        <div class="w-full max-w-md">
            <!-- Session Status -->
            @if (session('status'))
                <div class="mb-4 p-4 rounded-lg bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800">
                    <p class="text-sm text-green-700 dark:text-green-300">{{ session('status') }}</p>
                </div>
            @endif
            
            <!-- Error Message -->
            @if (session('error'))
                <div class="mb-4 p-4 rounded-lg bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800">
                    <p class="text-sm text-red-700 dark:text-red-300">{{ session('error') }}</p>
                </div>
            @endif
            
            <!-- Session Expired -->
            @if (session('session_expired'))
                <div class="mb-4 p-4 rounded-lg bg-yellow-50 dark:bg-yellow-900/30 border border-yellow-200 dark:border-yellow-800">
                    <p class="text-sm text-yellow-700 dark:text-yellow-300">
                        {{ __('Su sesión ha expirado por inactividad.') }}
                    </p>
                </div>
            @endif
            
            <!-- Main Card -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                {{ $slot }}
            </div>
        </div>
        
        <!-- Footer -->
        <div class="mt-8 text-center text-sm text-gray-500 dark:text-gray-400">
            <p>&copy; {{ date('Y') }} Vespot. Todos los derechos reservados.</p>
            <p class="mt-1 text-xs">
                Cumple con Resolución 1995 de 1999 y Ley 1581 de 2012
            </p>
        </div>
    </div>
    
    @stack('scripts')
    @livewireScripts
</body>
</html>