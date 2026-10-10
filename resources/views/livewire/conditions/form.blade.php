<x-slot name="header">{{ __('Nuevo Diagnóstico') }}</x-slot>

<div class="max-w-3xl mx-auto">
    @if($patient)
    <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/30 rounded-lg border border-blue-200 dark:border-blue-800">
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
    @endif

    <form wire:submit="save" class="space-y-6">
        @csrf

        <!-- CIE-10 Search -->
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                {{ __('Buscar diagnóstico (CIE-10)') }}
            </label>

            @if($selectedCode)
                <div class="p-4 bg-green-50 dark:bg-green-900/30 rounded-lg border border-green-200 dark:border-green-800">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="font-bold text-green-800 dark:text-green-200">{{ $selectedCode }}</span>
                            <span class="text-green-700 dark:text-green-300 ml-2">{{ $selectedDisplay }}</span>
                        </div>
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
                        placeholder="{{ __('Escriba código o descripción...') }}"
                        class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                    />

                    @if(count($results) > 0)
                        <div class="absolute z-10 mt-1 w-full bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 max-h-60 overflow-y-auto">
                            @foreach($results as $result)
                                <button
                                    type="button"
                                    wire:click="selectCode('{{ $result['code'] }}', '{{ $result['display'] }}')"
                                    class="w-full px-4 py-3 text-left hover:bg-gray-50 dark:hover:bg-gray-700 border-b border-gray-100 dark:border-gray-700 last:border-0"
                                >
                                    <span class="font-bold text-blue-600 dark:text-blue-400">{{ $result['code'] }}</span>
                                    <span class="text-gray-700 dark:text-gray-300 ml-2">{{ $result['display'] }}</span>
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif

            @error('selectedCode')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Category -->
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                {{ __('Tipo de diagnóstico') }}
            </label>
            <select
                wire:model="category"
                class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
            >
                <option value="principal">{{ __('Principal') }}</option>
                <option value="secondary">{{ __('Secundario') }}</option>
                <option value="admission">{{ __('De ingreso') }}</option>
                <option value="discharge">{{ __('De egreso') }}</option>
            </select>
        </div>

        <!-- Clinical Status -->
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                {{ __('Estado clínico') }}
            </label>
            <select
                wire:model="clinicalStatus"
                class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
            >
                <option value="active">{{ __('Activo') }}</option>
                <option value="recurrence">{{ __('Recurrencia') }}</option>
                <option value="relapse">{{ __('Recaída') }}</option>
                <option value="inactive">{{ __('Inactivo') }}</option>
                <option value="remission">{{ __('Remisión') }}</option>
                <option value="resolved">{{ __('Resuelto') }}</option>
            </select>
        </div>

        <!-- Verification Status -->
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                {{ __('Estado de verificación') }}
            </label>
            <select
                wire:model="verificationStatus"
                class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
            >
                <option value="confirmed">{{ __('Confirmado') }}</option>
                <option value="unconfirmed">{{ __('Sin confirmar') }}</option>
                <option value="provisional">{{ __('Provisional') }}</option>
                <option value="differential">{{ __('Diferencial') }}</option>
            </select>
        </div>

        <!-- Onset Date -->
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                {{ __('Fecha de inicio') }}
            </label>
            <input
                type="date"
                wire:model="onsetDate"
                class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
            />
        </div>

        <!-- Notes -->
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                {{ __('Notas adicionales') }}
            </label>
            <textarea
                wire:model="note"
                rows="3"
                placeholder="{{ __('Observaciones, hallazgos clínicos...') }}"
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
                <span wire:loading.remove>{{ __('Guardar diagnóstico') }}</span>
                <span wire:loading>{{ __('Guardando...') }}</span>
            </button>
        </div>
    </form>
</div>