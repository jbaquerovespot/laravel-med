@props([
    'systolicBp' => '',
    'diastolicBp' => '',
    'heartRate' => '',
    'respiratoryRate' => '',
    'temperature' => '',
    'spo2' => '',
    'weight' => '',
    'height' => '',
    'bmi' => null,
    'errors' => [],
])

<div class="space-y-6">
    <!-- Blood Pressure -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label for="systolicBp" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                {{ __('Presión Sistólica (mmHg)') }}
            </label>
            <div class="relative">
                <input
                    type="number"
                    id="systolicBp"
                    wire:model.live="systolicBp"
                    placeholder="120"
                    class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm {{ isset($errors['systolicBp']) ? 'border-red-500' : '' }}"
                />
                @if(isset($errors['systolicBp']))
                    <p class="mt-1 text-sm text-red-600">{{ $errors['systolicBp'] }}</p>
                @endif
            </div>
        </div>
        
        <div>
            <label for="diastolicBp" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                {{ __('Presión Diastólica (mmHg)') }}
            </label>
            <div class="relative">
                <input
                    type="number"
                    id="diastolicBp"
                    wire:model.live="diastolicBp"
                    placeholder="80"
                    class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm {{ isset($errors['diastolicBp']) ? 'border-red-500' : '' }}"
                />
                @if(isset($errors['diastolicBp']))
                    <p class="mt-1 text-sm text-red-600">{{ $errors['diastolicBp'] }}</p>
                @endif
            </div>
        </div>
    </div>

    <!-- BP Status Indicator -->
    @if($systolicBp && $diastolicBp)
        @php
            $bpStatus = 'normal';
            $bpMessage = 'Normal';
            if ($systolicBp >= 180 || $diastolicBp >= 120) {
                $bpStatus = 'critical';
                $bpMessage = 'Crisis Hipertensiva';
            } elseif ($systolicBp >= 140 || $diastolicBp >= 90) {
                $bpStatus = 'high';
                $bpMessage = 'Hipertensión';
            } elseif ($systolicBp < 90 || $diastolicBp < 60) {
                $bpStatus = 'low';
                $bpMessage = 'Hipotensión';
            }
        @endphp
        <div class="p-3 rounded-lg {{ $bpStatus === 'normal' ? 'bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-300' : ($bpStatus === 'critical' ? 'bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-300' : ($bpStatus === 'high' ? 'bg-yellow-50 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300' : 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300')) }}">
            <div class="flex items-center">
                @if($bpStatus !== 'normal')
                    <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                @endif
                <span class="font-medium">{{ $bpMessage }}</span>
            </div>
        </div>
    @endif

    <!-- Heart Rate & Respiratory Rate -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label for="heartRate" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                {{ __('Frecuencia Cardíaca (lpm)') }}
            </label>
            <input
                type="number"
                id="heartRate"
                wire:model.live="heartRate"
                placeholder="72"
                class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
            />
        </div>
        
        <div>
            <label for="respiratoryRate" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                {{ __('Frecuencia Respiratoria (rpm)') }}
            </label>
            <input
                type="number"
                id="respiratoryRate"
                wire:model.live="respiratoryRate"
                placeholder="16"
                class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
            />
        </div>
    </div>

    <!-- Temperature & SpO2 -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label for="temperature" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                {{ __('Temperatura (°C)') }}
            </label>
            <input
                type="number"
                step="0.1"
                id="temperature"
                wire:model.live="temperature"
                placeholder="36.5"
                class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
            />
        </div>
        
        <div>
            <label for="spo2" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                {{ __('SpO₂ (%)') }}
            </label>
            <input
                type="number"
                id="spo2"
                wire:model.live="spo2"
                placeholder="98"
                min="0"
                max="100"
                class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
            />
            @if($spo2 && $spo2 < 90)
                <p class="mt-1 text-sm text-red-600 flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    {{ __('Hipoxemia - Requiere atención') }}
                </p>
            @endif
        </div>
    </div>

    <!-- Weight & Height -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label for="weight" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                {{ __('Peso (kg)') }}
            </label>
            <input
                type="number"
                step="0.1"
                id="weight"
                wire:model.live="weight"
                placeholder="70"
                class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
            />
        </div>
        
        <div>
            <label for="height" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                {{ __('Talla (cm)') }}
            </label>
            <input
                type="number"
                id="height"
                wire:model.live="height"
                placeholder="170"
                class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
            />
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                {{ __('IMC') }}
            </label>
            @if($bmi)
                @php
                    $bmiCategory = 'normal';
                    $bmiColor = 'green';
                    if ($bmi < 18.5) {
                        $bmiCategory = 'Bajo peso';
                        $bmiColor = 'blue';
                    } elseif ($bmi >= 25 && $bmi < 30) {
                        $bmiCategory = 'Sobrepeso';
                        $bmiColor = 'yellow';
                    } elseif ($bmi >= 30) {
                        $bmiCategory = 'Obesidad';
                        $bmiColor = 'red';
                    }
                @endphp
                <div class="p-2 rounded-lg bg-{{ $bmiColor }}-50 dark:bg-{{ $bmiColor }}-900/30">
                    <span class="text-lg font-bold text-{{ $bmiColor }}-700 dark:text-{{ $bmiColor }}-300">{{ number_format($bmi, 1) }}</span>
                    <span class="text-sm text-{{ $bmiColor }}-600 dark:text-{{ $bmiColor }}-400 ml-2">{{ $bmiCategory }}</span>
                </div>
            @else
                <div class="p-2 rounded-lg bg-gray-50 dark:bg-gray-700">
                    <span class="text-gray-400 dark:text-gray-500">{{ __('Ingrese peso y talla') }}</span>
                </div>
            @endif
        </div>
    </div>
</div>