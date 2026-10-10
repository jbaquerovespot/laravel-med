<x-slot name="header">
    {{ $patient ? __('Nueva Consulta - ') . $patient->full_name : __('Nueva Consulta') }}
</x-slot>

<div class="max-w-5xl mx-auto">
    <!-- Step Indicator -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            @php
                $steps = [
                    1 => __('Paciente'),
                    2 => __('Motivo'),
                    3 => __('Signos Vitales'),
                    4 => __('Diagnóstico'),
                    5 => __('Tratamiento'),
                ];
            @endphp
            @foreach($steps as $step => $label)
                <div class="flex items-center {{ $step < $totalSteps ? 'flex-1' : '' }}">
                    <div class="flex flex-col items-center">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center font-semibold text-sm
                            {{ $currentStep >= $step ? 'bg-blue-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-500' }}">
                            {{ $currentStep > $step ? '✓' : $step }}
                        </div>
                        <span class="mt-2 text-xs text-gray-500 dark:text-gray-400 hidden sm:block">{{ $label }}</span>
                    </div>
                    @if($step < $totalSteps)
                    <div class="flex-1 h-1 mx-2 {{ $currentStep > $step ? 'bg-blue-600' : 'bg-gray-200 dark:bg-gray-700' }}"></div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
        <!-- Step 1: Patient Selection -->
        @if($currentStep === 1)
        <div class="p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('Seleccionar Paciente') }}</h2>
            
            @if($patient)
                <div class="p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg mb-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-medium text-green-800 dark:text-green-200">{{ $patient->full_name }}</p>
                            <p class="text-sm text-green-600 dark:text-green-400">{{ $patient->document_number }}</p>
                        </div>
                        <button wire:click="$set('patient', null); $set('patientId', null)" 
                                class="text-sm text-green-700 hover:text-green-800">
                            {{ __('Cambiar') }}
                        </button>
                    </div>
                </div>
            @else
                <div class="relative mb-4">
                    <input type="text" wire:model.live.debounce.300ms="patientSearch"
                           placeholder="{{ __('Buscar por documento o nombre...') }}"
                           class="block w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>

                @if($patientResults->count() > 0)
                <div class="divide-y divide-gray-200 dark:divide-gray-700 border border-gray-200 dark:border-gray-700 rounded-lg">
                    @foreach($patientResults as $p)
                    <button wire:click="selectPatient({{ $p->id }})" 
                            class="w-full px-4 py-3 text-left hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center justify-between">
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">{{ $p->full_name }}</p>
                            <p class="text-sm text-gray-500">{{ $p->document_number }}</p>
                        </div>
                        <span class="text-sm text-gray-400">{{ $p->birth_date?->age ?? '-' }} años</span>
                    </button>
                    @endforeach
                </div>
                @elseif(strlen($patientSearch) >= 2)
                <p class="text-center text-gray-500 py-4">{{ __('No se encontraron pacientes') }}</p>
                @endif
            @endif
        </div>

        <!-- Step 2: Reason for Visit -->
        @elseif($currentStep === 2)
        <div class="p-6 space-y-4">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('Motivo de Consulta') }}</h2>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    {{ __('Motivo Principal') }} <span class="text-red-500">*</span>
                </label>
                <input type="text" wire:model="chiefComplaint"
                       placeholder="{{ __('Ej: Dolor de cabeza, Fiebre, Control...' ) }}"
                       class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Anamnesis') }}</label>
                <textarea wire:model="anamnesis" rows="4"
                          placeholder="{{ __('Describa la historia clínica actual...' ) }}"
                          class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"></textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Antecedentes Relacionados') }}</label>
                <textarea wire:model="relatedHistory" rows="2"
                          class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"></textarea>
            </div>
        </div>

        <!-- Step 3: Vital Signs -->
        @elseif($currentStep === 3)
        <div class="p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('Signos Vitales') }}</h2>
            
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">{{ __('Presión Sistólica') }}</label>
                    <div class="flex">
                        <input type="number" wire:model="systolicBp" class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-l-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        <span class="px-3 py-2 bg-gray-100 dark:bg-gray-600 border border-l-0 border-gray-300 dark:border-gray-600 rounded-r-lg text-gray-500">mmHg</span>
                    </div>
                </div>
                <div>
                    <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">{{ __('Presión Diastólica') }}</label>
                    <div class="flex">
                        <input type="number" wire:model="diastolicBp" class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-l-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        <span class="px-3 py-2 bg-gray-100 dark:bg-gray-600 border border-l-0 border-gray-300 dark:border-gray-600 rounded-r-lg text-gray-500">mmHg</span>
                    </div>
                </div>
                <div>
                    <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">{{ __('Frecuencia Cardíaca') }}</label>
                    <div class="flex">
                        <input type="number" wire:model="heartRate" class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-l-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        <span class="px-3 py-2 bg-gray-100 dark:bg-gray-600 border border-l-0 border-gray-300 dark:border-gray-600 rounded-r-lg text-gray-500">/min</span>
                    </div>
                </div>
                <div>
                    <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">{{ __('Frecuencia Respiratoria') }}</label>
                    <div class="flex">
                        <input type="number" wire:model="respiratoryRate" class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-l-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        <span class="px-3 py-2 bg-gray-100 dark:bg-gray-600 border border-l-0 border-gray-300 dark:border-gray-600 rounded-r-lg text-gray-500">/min</span>
                    </div>
                </div>
                <div>
                    <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">{{ __('Temperatura') }}</label>
                    <div class="flex">
                        <input type="number" step="0.1" wire:model="temperature" class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-l-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        <span class="px-3 py-2 bg-gray-100 dark:bg-gray-600 border border-l-0 border-gray-300 dark:border-gray-600 rounded-r-lg text-gray-500">°C</span>
                    </div>
                </div>
                <div>
                    <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">{{ __('SpO2') }}</label>
                    <div class="flex">
                        <input type="number" wire:model="spo2" class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-l-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        <span class="px-3 py-2 bg-gray-100 dark:bg-gray-600 border border-l-0 border-gray-300 dark:border-gray-600 rounded-r-lg text-gray-500">%</span>
                    </div>
                </div>
                <div>
                    <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">{{ __('Peso') }}</label>
                    <div class="flex">
                        <input type="number" step="0.1" wire:model="weight" class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-l-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        <span class="px-3 py-2 bg-gray-100 dark:bg-gray-600 border border-l-0 border-gray-300 dark:border-gray-600 rounded-r-lg text-gray-500">kg</span>
                    </div>
                </div>
                <div>
                    <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">{{ __('Talla') }}</label>
                    <div class="flex">
                        <input type="number" wire:model="height" class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-l-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        <span class="px-3 py-2 bg-gray-100 dark:bg-gray-600 border border-l-0 border-gray-300 dark:border-gray-600 rounded-r-lg text-gray-500">cm</span>
                    </div>
                </div>
            </div>

            @if($bmi)
            <div class="mt-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                <span class="text-sm text-blue-800 dark:text-blue-200">
                    {{ __('IMC calculado') }}: <strong>{{ $bmi }}</strong> kg/m²
                </span>
            </div>
            @endif
        </div>

        <!-- Step 4: Diagnosis -->
        @elseif($currentStep === 4)
        <div class="p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('Diagnóstico') }}</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div class="md:col-span-2">
                    <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">{{ __('Buscar diagnóstico (CIE-10)' ) }}</label>
                    <input type="text" wire:model="selectedDiagnosisDisplay" placeholder="{{ __('Ej: Diabetes mellitus, Hipertensión...' ) }}"
                           class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">{{ __('Código CIE-10' ) }}</label>
                    <input type="text" wire:model="selectedDiagnosisCode" placeholder="{{ __('Ej: E11, I10...' ) }}"
                           class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
            </div>
            
            <div class="flex items-center gap-4 mb-4">
                <select wire:model="diagnosisType" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    <option value="principal">{{ __('Principal') }}</option>
                    <option value="secondary">{{ __('Secundario') }}</option>
                </select>
                <button wire:click="addDiagnosis" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    {{ __('Agregar') }}
                </button>
            </div>

            @if(count($diagnoses) > 0)
            <div class="border border-gray-200 dark:border-gray-700 rounded-lg divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($diagnoses as $index => $diagnosis)
                <div class="px-4 py-3 flex items-center justify-between">
                    <div>
                        <span class="font-medium text-gray-900 dark:text-white">{{ $diagnosis['code'] }}</span>
                        <span class="text-gray-500 ml-2">{{ $diagnosis['display'] }}</span>
                        <span class="ml-2 px-2 py-0.5 text-xs rounded-full {{ $diagnosis['type'] === 'principal' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-600' }}">
                            {{ $diagnosis['type'] }}
                        </span>
                    </div>
                    <button wire:click="removeDiagnosis({{ $index }})" class="text-red-600 hover:text-red-700">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                @endforeach
            </div>
            @endif
        </div>

        <!-- Step 5: Treatment -->
        @elseif($currentStep === 5)
        <div class="p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('Tratamiento') }}</h2>
            
            <!-- Allergy Warnings -->
            @if(count($allergyWarnings) > 0)
            <div class="mb-4 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                <p class="font-medium text-red-800 dark:text-red-200 mb-2">{{ __('¡Alerta de Alergia!' ) }}</p>
                @foreach($allergyWarnings as $warning)
                <p class="text-sm text-red-700 dark:text-red-300">{{ $warning['medication'] }}: {{ $warning['reaction'] }}</p>
                @endforeach
            </div>
            @endif

            <!-- Interaction Warnings -->
            @if(count($interactionWarnings) > 0)
            <div class="mb-4 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                <p class="font-medium text-yellow-800 dark:text-yellow-200 mb-2">{{ __('Posible Interacción' ) }}</p>
                @foreach($interactionWarnings as $warning)
                <p class="text-sm text-yellow-700 dark:text-yellow-300">{{ $warning['drugs'] }}: {{ $warning['description'] }}</p>
                @endforeach
            </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">{{ __('Medicamento' ) }}</label>
                    <input type="text" wire:model="selectedMedication" placeholder="{{ __('Nombre del medicamento' ) }}"
                           class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">{{ __('Dosis' ) }}</label>
                    <input type="text" wire:model="dosage" placeholder="{{ __('Ej: 500mg, 10mL...' ) }}"
                           class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">{{ __('Frecuencia' ) }}</label>
                    <input type="text" wire:model="frequency" placeholder="{{ __('Ej: Cada 8 horas, 2 veces al día...' ) }}"
                           class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">{{ __('Duración' ) }}</label>
                    <input type="text" wire:model="duration" placeholder="{{ __('Ej: 7 días, 1 mes...' ) }}"
                           class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">{{ __('Instrucciones' ) }}</label>
                    <input type="text" wire:model="instructions" placeholder="{{ __('Indicaciones adicionales...' ) }}"
                           class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
            </div>
            
            <button wire:click="addMedication" class="mb-4 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                {{ __('Agregar Medicamento' ) }}
            </button>

            @if(count($medications) > 0)
            <div class="border border-gray-200 dark:border-gray-700 rounded-lg divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($medications as $index => $med)
                <div class="px-4 py-3 flex items-center justify-between">
                    <div>
                        <p class="font-medium text-gray-900 dark:text-white">{{ $med['name'] }}</p>
                        <p class="text-sm text-gray-500">{{ $med['dosage'] }} - {{ $med['frequency'] }} - {{ $med['duration'] }}</p>
                    </div>
                    <button wire:click="removeMedication({{ $index }})" class="text-red-600 hover:text-red-700">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                @endforeach
            </div>
            @endif
        </div>
        @endif

        <!-- Navigation Buttons -->
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
            @if($currentStep > 1)
            <button wire:click="previousStep" class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">
                {{ __('Anterior' ) }}
            </button>
            @else
            <div></div>
            @endif

            @if($currentStep < $totalSteps)
            <button wire:click="nextStep" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                {{ __('Siguiente' ) }}
            </button>
            @else
            <button wire:click="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                {{ __('Finalizar Consulta' ) }}
            </button>
            @endif
        </div>
    </div>
</div>