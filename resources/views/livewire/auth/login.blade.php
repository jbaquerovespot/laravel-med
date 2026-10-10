<div class="p-8">
    <div class="text-center mb-6">
        <h2 class="text-xl font-semibold text-gray-800 dark:text-white">
            {{ __('Iniciar Sesión') }}
        </h2>
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
            {{ __('Ingrese sus credenciales para acceder al sistema') }}
        </p>
    </div>
    
    <form wire:submit="login" class="space-y-6">
        <!-- Email Field -->
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                {{ __('Correo Electrónico') }}
            </label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                    </svg>
                </div>
                <input
                    type="email"
                    id="email"
                    wire:model="email"
                    autocomplete="email"
                    autofocus
                    {{ $isLocked ? 'disabled' : '' }}
                    class="block w-full pl-10 pr-3 py-2.5 border rounded-lg shadow-sm transition
                        {{ $errors->has('email') ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500' }}
                        bg-white dark:bg-gray-700 text-gray-900 dark:text-white
                        placeholder-gray-400 dark:placeholder-gray-500
                        disabled:bg-gray-100 dark:disabled:bg-gray-800 disabled:cursor-not-allowed"
                    placeholder="usuario@vespot.co"
                >
            </div>
            @if($errors->has('email'))
                <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $errors->first('email') }}</p>
            @endif
        </div>
        
        <!-- Password Field -->
        <div>
            <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                {{ __('Contraseña') }}
            </label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
                <input
                    type="password"
                    id="password"
                    wire:model="password"
                    autocomplete="current-password"
                    {{ $isLocked ? 'disabled' : '' }}
                    class="block w-full pl-10 pr-3 py-2.5 border rounded-lg shadow-sm transition
                        {{ $errors->has('password') ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500' }}
                        bg-white dark:bg-gray-700 text-gray-900 dark:text-white
                        placeholder-gray-400 dark:placeholder-gray-500
                        disabled:bg-gray-100 dark:disabled:bg-gray-800 disabled:cursor-not-allowed"
                    placeholder="••••••••"
                >
            </div>
            @if($errors->has('password'))
                <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $errors->first('password') }}</p>
            @endif
        </div>
        
        <!-- Remember Me -->
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <input
                    type="checkbox"
                    id="remember"
                    wire:model="remember"
                    {{ $isLocked ? 'disabled' : '' }}
                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 rounded
                        disabled:opacity-50 disabled:cursor-not-allowed"
                >
                <label for="remember" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                    {{ __('Recordarme') }}
                </label>
            </div>
        </div>
        
        <!-- Lockout Warning -->
        @if($isLocked)
            <div class="p-4 rounded-lg bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800">
                <div class="flex items-center">
                    <svg class="h-5 w-5 text-red-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <span class="text-sm text-red-700 dark:text-red-300">
                        {{ __('Cuenta bloqueada. Intente nuevamente en :seconds segundos.', ['seconds' => $lockoutSeconds]) }}
                    </span>
                </div>
            </div>
            
            <!-- Poll for lockout status update -->
            <div wire:poll.1s="refreshLockout" class="hidden"></div>
        @endif
        
        <!-- Submit Button -->
        <div>
            <button
                type="submit"
                wire:loading.attr="disabled"
                {{ $isLocked ? 'disabled' : '' }}
                class="w-full flex justify-center items-center py-2.5 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white
                    bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500
                    disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-blue-600
                    transition-colors duration-200"
            >
                <svg wire:loading.remove wire:target="login" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                </svg>
                <svg wire:loading wire:target="login" class="animate-spin h-5 w-5 mr-2 text-white" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>{{ __('Iniciar Sesión') }}</span>
            </button>
        </div>
    </form>
    
    <!-- Security Notice -->
    <div class="mt-6 p-4 rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800">
        <div class="flex items-start">
            <svg class="h-5 w-5 text-blue-500 mt-0.5 mr-2 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div class="text-xs text-blue-700 dark:text-blue-300">
                <p class="font-medium">{{ __('Seguridad') }}</p>
                <p class="mt-1">{{ __('Su sesión expirará después de 15 minutos de inactividad.') }}</p>
            </div>
        </div>
    </div>
</div>