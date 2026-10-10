<x-slot name="header">{{ __('Prescribir Medicamento') }}</x-slot>

<div class="max-w-3xl mx-auto">
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
        </div>
    </div>
    @endif

    <!-- Allergy Alert -->
    @if($patient && $patient->allergyIntolerances->count() > 0)
    <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/30 rounded-lg border border-red-200 dark:border-red-800">
        <div class="flex items-start">
            <svg class="w-5 h-5 text-red-600 dark:text-red-400 mr-2 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <div>
                <p class="font-medium text-red-800 dark:text-red-200">{{ __('Alérgico a:') }}</p>
                <ul class="text-sm text-red-700 dark:text-red-300 mt-1">
                    @foreach($patient->allergyIntolerances as $allergy)
                    <li>{{ $allergy->substance ?? $allergy->note }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
    @endif

    <form wire:submit="save" class="space-y-6">
        @csrf

        <!-- Medication Selection -->
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                {{ __('Medicamento') }}
            </label>

            @if($selectedMedication)
                <div class="p-4 bg-green-50 dark:bg-green-900/30 rounded-lg border border-green-200 dark:border-green-800">
                    <div class="flex items-center justify-between">
                        <span class="font-medium text-green-800 dark:text-green-200">{{ $selectedMedication }}</span>
                        <button type="button" wire:click="clearSelection" class="text-red-600 hover:text-red-800">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            @else
                <div class="relative">
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="{{ __('Buscar medicamento...') }}"
                        class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                    />

                    @if(count($filteredMedications) > 0)
                        <div class="absolute z-10 mt-1 w-full bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 max-h-60 overflow-y-auto">
                            @foreach($filteredMedications as $med)
                                <button
                                    type="button"
                                    wire:click="selectMedication('{{ $med['name'] }}', '{{ $med['dosage'] }}', '{{ $med['frequency'] }}')"
                                    class="w-full px-4 py-3 text-left hover:bg-gray-50 dark:hover:bg-gray-700 border-b border-gray-100 dark:border-gray-700 last:border-0"
                                >
                                    <span class="font-medium text-blue-600 dark:text-blue-400">{{ $med['name'] }}</span>
                                    <span class="text-gray-500 dark:text-gray-400 text-sm ml-2">{{ $med['dosage'] }}</span>
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif

            @error('selectedMedication')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Warnings -->
        @if($hasWarnings)
            @if(count($allergyWarnings) > 0)
                <div class="p-4 bg-red-100 dark:bg-red-900/50 rounded-lg border border-red-300 dark:border-red-700">
                    <div class="flex items-center mb-2">
                        <svg class="w-5 h-5 text-red-600 dark:text-red-400 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <span class="font-bold text-red-800 dark:text-red-200">{{ __('¡ALERGIA DETECTADA!') }}</span>
                    </div>
                    @foreach($allergyWarnings as $warning)
                        <p class="text-sm text-red-700 dark:text-red-300">
                            {{ $warning['reaction'] }} ({{ $warning['severity'] }})
                        </p>
                    @endforeach
                </div>
            @endif

            @if(count($interactionWarnings) > 0)
                <div class="p-4 bg-yellow-100 dark:bg-yellow-900/50 rounded-lg border border-yellow-300 dark:border-yellow-700">
                    <div class="flex items-center mb-2">
                        <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="font-bold text-yellow-800 dark:text-yellow-200">{{ __('Interacciones detectadas') }}</span>
                    </div>
                    @foreach($interactionWarnings as $warning)
                        <div class="text-sm text-yellow-700 dark:text-yellow-300 mb-2">
                            <span class="font-medium">{{ $warning['drugs'] }}:</span>
                            {{ $warning['description'] }}
                        </div>
                    @endforeach
                </div>
            @endif
        @endif

        <!-- Dosage -->
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                {{ __('Dosis') }}
            </label>
            <input
                type="text"
                wire:model="dosage"
                placeholder="ej: 500mg, 1 tableta, 5ml"
                class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
            />
            @error('dosage')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Frequency -->
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                {{ __('Frecuencia') }}
            </label>
            <select
                wire:model="frequency"
                class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
            >
                <option value="">{{ __('Seleccionar...') }}</option>
                <option value="una vez al día">Una vez al día (QD)</option>
                <option value="cada 12 horas">Cada 12 horas (BID)</option>
                <option value="cada 8 horas">Cada 8 horas (TID)</option>
                <option value="cada 6 horas">Cada 6 horas (QID)</option>
                <option value="cada 4 horas">Cada 4 horas</option>
                <option value="una vez a la semana">Una vez a la semana</option>
                <option value="según necesidad">Según necesidad (PRN)</option>
            </select>
            @error('frequency')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Duration -->
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                {{ __('Duración del tratamiento') }}
            </label>
            <input
                type="text"
                wire:model="duration"
                placeholder="ej: 7 días, 1 mes, uso continuo"
                class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
            />
            @error('duration')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Quantity -->
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                {{ __('Cantidad a dispensar') }}
            </label>
            <input
                type="text"
                wire:model="quantity"
                placeholder="ej: 20 tabletas, 100ml"
                class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
            />
        </div>

        <!-- Instructions -->
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                {{ __('Instrucciones adicionales') }}
            </label>
            <textarea
                wire:model="instructions"
                rows="3"
                placeholder="{{ __('Tomar con alimentos, evitar alcohol, etc.') }}"
                class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
            ></textarea>
        </div>

        @error('general')
            <div class="p-4 bg-red-50 dark:bg-red-900/30 rounded-lg border border-red-200 dark:border-red-800">
                <p class="text-sm text-red-700 dark:text-red-300">{{ $message }}</p>
            </div>
        @enderror

        <!-- Actions -->
        <div class="flex items-center justify-end space-x-3 pt-4 border-t border-gray-200 dark:border-gray-700">
            <a
                href="{{ route('patients.show', $patientId) }}"
                wire:navigate
                class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700"
            >
                {{ __('Cancelar') }}
            </a>
            <button
                type="submit"
                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                wire:loading.attr="disabled"
            >
                <span wire:loading.remove>{{ __('Prescribir') }}</span>
                <span wire:loading>{{ __('Guardando...') }}</span>
            </button>
        </div>
    </form>
</div>