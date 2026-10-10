<x-slot name="header">{{ __('Identificación Biométrica') }}</x-slot>

<div class="max-w-2xl mx-auto">
    @if(!$biometricEnabled)
        <div class="bg-yellow-50 dark:bg-yellow-900/30 border border-yellow-200 dark:border-yellow-800 rounded-lg p-6 mb-6">
            <div class="flex items-center">
                <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <div>
                    <h3 class="font-medium text-yellow-800 dark:text-yellow-200">{{ __('Sistema biométrico deshabilitado') }}</h3>
                    <p class="text-sm text-yellow-700 dark:text-yellow-300 mt-1">
                        {{ __('Habilite HCE_BIOMETRIC_ENABLED=true en el archivo .env') }}
                    </p>
                </div>
            </div>
        </div>
    @endif

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <!-- Header -->
        <div class="p-6 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/30 dark:to-indigo-900/30">
            <div class="flex items-center justify-center">
                <svg class="w-16 h-16 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 008 4.07M3 15.364c.64-1.319 1-2.8 1-4.364 0-1.457.39-2.823 1.07-4" />
                </svg>
            </div>
            <h2 class="text-xl font-bold text-center text-gray-900 dark:text-white mt-4">
                {{ __('Identificación por Huella') }}
            </h2>
            <p class="text-center text-gray-500 dark:text-gray-400 mt-2">
                {{ __('Coloque su dedo en el lector biométrico para identificarse') }}
            </p>
        </div>

        <!-- Content -->
        <div class="p-6">
            @if($status === 'ready')
                <div class="text-center">
                    <button
                        type="button"
                        wire:click="startIdentification"
                        wire:loading.attr="disabled"
                        class="w-full py-6 px-8 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white text-lg font-medium rounded-xl transition-colors flex items-center justify-center"
                        @if(!$biometricEnabled) disabled @endif
                    >
                        <svg wire:loading.remove class="w-8 h-8 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11.5V14m0-2.5v-6a1.5 1.5 0 113 0m-3 6a1.5 1.5 0 00-3 0v2a7.5 7.5 0 0015 0v-5a1.5 1.5 0 00-3 0m-6-3V11m0-5.5v-1a1.5 1.5 0 013 0v1m0 0V11m0-5.5a1.5 1.5 0 013 0v3m0 0V11" />
                        </svg>
                        <svg wire:loading class="animate-spin w-8 h-8 mr-3" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span wire:loading.remove>{{ __('Iniciar identificación') }}</span>
                        <span wire:loading>{{ __('Identificando...') }}</span>
                    </button>
                </div>
            @endif

            @if($status === 'capturing')
                <div class="text-center py-8">
                    <div class="inline-flex items-center justify-center w-24 h-24 rounded-full bg-blue-100 dark:bg-blue-900/50 mb-4">
                        <svg class="animate-pulse w-12 h-12 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11.5V14m0-2.5v-6a1.5 1.5 0 113 0m-3 6a1.5 1.5 0 00-3 0v2a7.5 7.5 0 0015 0v-5a1.5 1.5 0 00-3 0m-6-3V11m0-5.5v-1a1.5 1.5 0 013 0v1m0 0V11m0-5.5a1.5 1.5 0 013 0v3m0 0V11" />
                        </svg>
                    </div>
                    <p class="text-lg font-medium text-gray-900 dark:text-white">{{ __('Coloque su dedo en el lector') }}</p>
                    <p class="text-gray-500 dark:text-gray-400 mt-2">{{ __('Esperando captura de huella...') }}</p>
                </div>
            @endif

            @if($status === 'success' && $identifiedPatient)
                <div class="text-center">
                    <!-- Success Icon -->
                    <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-green-100 dark:bg-green-900/50 mb-4">
                        <svg class="w-10 h-10 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>

                    <h3 class="text-xl font-bold text-green-700 dark:text-green-300 mb-2">
                        {{ __('Paciente Identificado') }}
                    </h3>

                    <!-- Patient Card -->
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 mb-4 text-left">
                        <div class="flex items-center">
                            <div class="w-12 h-12 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center">
                                <span class="text-lg font-bold text-blue-600 dark:text-blue-400">
                                    {{ strtoupper(substr($identifiedPatient->full_name, 0, 1)) }}
                                </span>
                            </div>
                            <div class="ml-4">
                                <p class="font-medium text-gray-900 dark:text-white">{{ $identifiedPatient->full_name }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $identifiedPatient->document_number }}</p>
                            </div>
                        </div>

                        @if($matchScore)
                            <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-600">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-500 dark:text-gray-400">{{ __('Nivel de coincidencia') }}</span>
                                    <span class="font-medium {{ $matchScore >= 80 ? 'text-green-600' : 'text-yellow-600' }}">{{ $matchScore }}%</span>
                                </div>
                                <div class="mt-1 w-full bg-gray-200 dark:bg-gray-600 rounded-full h-1.5">
                                    <div class="h-1.5 rounded-full {{ $matchScore >= 80 ? 'bg-green-500' : 'bg-yellow-500' }}" style="width: {{ $matchScore }}%"></div>
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Actions -->
                    <div class="flex space-x-3">
                        <button
                            type="button"
                            wire:click="newIdentification"
                            class="flex-1 py-3 px-4 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600"
                        >
                            {{ __('Nueva búsqueda') }}
                        </button>
                        <button
                            type="button"
                            wire:click="goToPatient"
                            class="flex-1 py-3 px-4 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg"
                        >
                            {{ __('Ver paciente') }}
                        </button>
                    </div>
                </div>
            @endif

            @if($status === 'error')
                <div class="text-center">
                    <!-- Error Icon -->
                    <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-red-100 dark:bg-red-900/50 mb-4">
                        <svg class="w-10 h-10 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </div>

                    <h3 class="text-xl font-bold text-red-700 dark:text-red-300 mb-2">
                        {{ __('Identificación fallida') }}
                    </h3>

                    <p class="text-gray-500 dark:text-gray-400 mb-6">
                        {{ $statusMessage }}
                    </p>

                    <button
                        type="button"
                        wire:click="newIdentification"
                        class="py-3 px-6 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg"
                    >
                        {{ __('Intentar nuevamente') }}
                    </button>
                </div>
            @endif
        </div>

        <!-- Footer Info -->
        <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-center text-sm text-gray-500 dark:text-gray-400">
                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                {{ __('Cumple con Ley 1581 de 2012 y Decreto 1377 de 2013') }}
            </div>
        </div>
    </div>
</div>