<x-slot name="header">{{ __('Enrolamiento Biométrico') }}</x-slot>

<div class="max-w-4xl mx-auto">
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

    @if($patient)
        <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/30 rounded-lg border border-blue-200 dark:border-blue-800">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    <div>
                        <p class="font-medium text-blue-800 dark:text-blue-200">{{ $patient->full_name }}</p>
                        <p class="text-sm text-blue-600 dark:text-blue-400">{{ $patient->document_number }}</p>
                    </div>
                </div>
                @if($patient->biometric_enrolled)
                    <span class="px-3 py-1 bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-200 text-sm font-medium rounded-full">
                        {{ __('Enrolado') }}
                    </span>
                @endif
            </div>
        </div>
    @else
        <div class="mb-6">
            <p class="text-gray-500 dark:text-gray-400">{{ __('Seleccione un paciente para enrolar huellas.') }}</p>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Finger Selection -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                {{ __('Seleccione el dedo') }}
            </h3>

            <div class="grid grid-cols-2 gap-2">
                @foreach($fingerOptions as $fingerId => $fingerName)
                    @php
                        $isEnrolled = in_array($fingerId, $enrolledFingers);
                        $isSelected = $fingerId === $selectedFinger;
                    @endphp
                    <button
                        type="button"
                        wire:click="selectFinger('{{ $fingerId }}')"
                        class="relative p-3 rounded-lg border text-left transition-colors {{
                            $isSelected
                                ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/30'
                                : 'border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700'
                        }}"
                    >
                        <div class="flex items-center justify-between">
                            <span class="{{ $isSelected ? 'text-blue-700 dark:text-blue-300 font-medium' : 'text-gray-700 dark:text-gray-300' }}">
                                {{ $fingerName }}
                            </span>
                            @if($isEnrolled)
                                <svg class="w-5 h-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            @endif
                        </div>
                    </button>
                @endforeach
            </div>

            <!-- Currently Enrolled Fingerprints -->
            @if(count($enrolledFingers) > 0)
                <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                        {{ __('Huellas enroladas') }}
                    </h4>
                    <div class="space-y-2">
                        @foreach($patient->fingerprints()->active()->get() as $fp)
                            <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-700 rounded">
                                <div>
                                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ $fp->finger_name }}</span>
                                    <span class="text-xs text-gray-400 ml-2">({{ $fp->quality_score }}%)</span>
                                </div>
                                <button
                                    type="button"
                                    wire:click="deleteFingerprint({{ $fp->id }})"
                                    wire:confirm="{{ __('¿Eliminar esta huella?') }}"
                                    class="text-red-600 hover:text-red-800 text-sm"
                                >
                                    {{ __('Eliminar') }}
                                </button>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <!-- Capture Area -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                {{ __('Captura de huella') }}
            </h3>

            <!-- Selected Finger Display -->
            <div class="mb-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Dedo seleccionado:') }}</p>
                <p class="text-lg font-medium text-gray-900 dark:text-white">{{ $fingerOptions[$selectedFinger] ?? $selectedFinger }}</p>
            </div>

            <!-- Capture Button -->
            <div class="mb-6">
                <button
                    type="button"
                    wire:click="startCapture"
                    wire:loading.attr="disabled"
                    class="w-full py-4 px-6 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white font-medium rounded-lg transition-colors flex items-center justify-center"
                    @if(!$biometricEnabled || !$patient) disabled @endif
                >
                    <svg wire:loading.remove class="w-6 h-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11.5V14m0-2.5v-6a1.5 1.5 0 113 0m-3 6a1.5 1.5 0 00-3 0v2a7.5 7.5 0 0015 0v-5a1.5 1.5 0 00-3 0m-6-3V11m0-5.5v-1a1.5 1.5 0 013 0v1m0 0V11m0-5.5a1.5 1.5 0 013 0v3m0 0V11" />
                    </svg>
                    <svg wire:loading class="animate-spin w-6 h-6 mr-2" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span wire:loading.remove>{{ __('Capturar huella') }}</span>
                    <span wire:loading>{{ __('Capturando...') }}</span>
                </button>
            </div>

            <!-- Status Display -->
            @if($status !== 'ready')
                <div class="p-4 rounded-lg {{
                    $status === 'success' ? 'bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800' :
                    ($status === 'error' ? 'bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800' :
                    'bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800')
                }}">
                    <div class="flex items-center">
                        @if($status === 'success')
                            <svg class="w-5 h-5 text-green-600 dark:text-green-400 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        @elseif($status === 'error')
                            <svg class="w-5 h-5 text-red-600 dark:text-red-400 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        @else
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mr-2 animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        @endif
                        <p class="text-sm {{
                            $status === 'success' ? 'text-green-700 dark:text-green-300' :
                            ($status === 'error' ? 'text-red-700 dark:text-red-300' : 'text-blue-700 dark:text-blue-300')
                        }}">
                            {{ $statusMessage }}
                        </p>
                    </div>

                    @if($capturedQuality)
                        <div class="mt-3">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ __('Calidad') }}</span>
                                <span class="text-xs font-medium {{
                                    $capturedQuality >= 80 ? 'text-green-600' :
                                    ($capturedQuality >= 60 ? 'text-yellow-600' : 'text-red-600')
                                }}">{{ $capturedQuality }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                                <div class="h-2 rounded-full {{
                                    $capturedQuality >= 80 ? 'bg-green-500' :
                                    ($capturedQuality >= 60 ? 'bg-yellow-500' : 'bg-red-500')
                                }}" style="width: {{ $capturedQuality }}%"></div>
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            <!-- Save Button -->
            @if($status === 'success' && $capturedTemplate)
                <div class="mt-6 flex space-x-3">
                    <button
                        type="button"
                        wire:click="resetCapture"
                        class="flex-1 py-3 px-4 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600"
                    >
                        {{ __('Nueva captura') }}
                    </button>
                    <button
                        type="button"
                        wire:click="saveEnrollment"
                        class="flex-1 py-3 px-4 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg"
                    >
                        {{ __('Guardar huella') }}
                    </button>
                </div>
            @endif
        </div>
    </div>

    <!-- Compliance Notice -->
    <div class="mt-6 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
        <div class="flex items-start">
            <svg class="w-5 h-5 text-gray-400 mr-2 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
            <div class="text-sm text-gray-500 dark:text-gray-400">
                <p class="font-medium text-gray-700 dark:text-gray-300">{{ __('Cumplimiento normativo') }}</p>
                <p class="mt-1">{{ __('Los datos biométricos son encriptados y almacenados de acuerdo con la Ley 1581 de 2012 y el Decreto 1377 de 2013.') }}</p>
            </div>
        </div>
    </div>
</div>