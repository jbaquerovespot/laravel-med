<x-slot name="header">{{ __('Orden de Laboratorio') }}</x-slot>

<div class="max-w-4xl mx-auto">
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

        <!-- Quick Profiles -->
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                {{ __('Perfiles rápidos') }}
            </label>
            <div class="flex flex-wrap gap-2">
                <button type="button" wire:click="addCommonProfile('hematology')" class="px-3 py-1.5 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600">
                    Hematología completa
                </button>
                <button type="button" wire:click="addCommonProfile('chemistry')" class="px-3 py-1.5 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600">
                    Cuadro hemático
                </button>
                <button type="button" wire:click="addCommonProfile('lipid')" class="px-3 py-1.5 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600">
                    Perfil lipídico
                </button>
                <button type="button" wire:click="addCommonProfile('thyroid')" class="px-3 py-1.5 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600">
                    Función tiroidea
                </button>
                <button type="button" wire:click="addCommonProfile('electrolytes')" class="px-3 py-1.5 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600">
                    Electrolitos
                </button>
                <button type="button" wire:click="addCommonProfile('coagulation')" class="px-3 py-1.5 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600">
                    Coagulación
                </button>
                <button type="button" wire:click="addCommonProfile('urinalysis')" class="px-3 py-1.5 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600">
                    Uroanálisis
                </button>
            </div>
        </div>

        <!-- Test Search -->
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                {{ __('Buscar exámenes') }}
            </label>
            <div class="relative">
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="{{ __('Escriba código, nombre o categoría...') }}"
                    class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                />

                @if(count($results) > 0)
                    <div class="absolute z-10 mt-1 w-full bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 max-h-80 overflow-y-auto">
                        @foreach($results as $test)
                            <button
                                type="button"
                                wire:click="addTest('{{ $test['code'] }}', '{{ $test['display'] }}', '{{ $test['category'] }}', '{{ $test['unit'] ?? '' }}')"
                                class="w-full px-4 py-3 text-left hover:bg-gray-50 dark:hover:bg-gray-700 border-b border-gray-100 dark:border-gray-700 last:border-0"
                            >
                                <div class="flex items-center justify-between">
                                    <div>
                                        <span class="font-bold text-blue-600 dark:text-blue-400">{{ $test['code'] }}</span>
                                        <span class="text-gray-700 dark:text-gray-300 ml-2">{{ $test['display'] }}</span>
                                    </div>
                                    <span class="text-xs text-gray-400 bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">{{ $test['category'] }}</span>
                                </div>
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <!-- Selected Tests -->
        <div>
            <div class="flex items-center justify-between mb-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('Exámenes seleccionados') }} ({{ count($selectedTests) }})
                </label>
                @if(count($selectedTests) > 0)
                    <button type="button" wire:click="clearTests" class="text-sm text-red-600 hover:text-red-800">
                        {{ __('Limpiar todo') }}
                    </button>
                @endif
            </div>

            @if(count($selectedTests) > 0)
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($selectedTests as $index => $test)
                        <div class="flex items-center justify-between px-4 py-3">
                            <div>
                                <span class="font-medium text-gray-900 dark:text-white">{{ $test['display'] }}</span>
                                <span class="text-sm text-gray-500 dark:text-gray-400 ml-2">({{ $test['code'] }})</span>
                                <span class="text-xs text-gray-400 bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded ml-2">{{ $test['category'] }}</span>
                            </div>
                            <button
                                type="button"
                                wire:click="removeTest({{ $index }})"
                                class="text-red-600 hover:text-red-800"
                            >
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-600 p-8 text-center">
                    <svg class="w-12 h-12 mx-auto text-gray-400 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    <p class="text-gray-500 dark:text-gray-400">{{ __('Seleccione exámenes de la lista o use un perfil rápido') }}</p>
                </div>
            @endif

            @error('selectedTests')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Priority -->
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                {{ __('Prioridad') }}
            </label>
            <div class="grid grid-cols-3 gap-3">
                <label class="relative flex cursor-pointer rounded-lg border {{ $priority === 'routine' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/30' : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800' }} p-4">
                    <input type="radio" wire:model="priority" value="routine" class="sr-only">
                    <div class="flex items-center">
                        <div class="text-sm">
                            <span class="font-medium {{ $priority === 'routine' ? 'text-blue-900 dark:text-blue-100' : 'text-gray-900 dark:text-white' }}">{{ __('Rutina') }}</span>
                            <p class="text-xs {{ $priority === 'routine' ? 'text-blue-600 dark:text-blue-300' : 'text-gray-500' }}">{{ __('24-48 horas') }}</p>
                        </div>
                    </div>
                </label>

                <label class="relative flex cursor-pointer rounded-lg border {{ $priority === 'urgent' ? 'border-yellow-500 bg-yellow-50 dark:bg-yellow-900/30' : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800' }} p-4">
                    <input type="radio" wire:model="priority" value="urgent" class="sr-only">
                    <div class="flex items-center">
                        <div class="text-sm">
                            <span class="font-medium {{ $priority === 'urgent' ? 'text-yellow-900 dark:text-yellow-100' : 'text-gray-900 dark:text-white' }}">{{ __('Urgente') }}</span>
                            <p class="text-xs {{ $priority === 'urgent' ? 'text-yellow-600 dark:text-yellow-300' : 'text-gray-500' }}">{{ __('12 horas') }}</p>
                        </div>
                    </div>
                </label>

                <label class="relative flex cursor-pointer rounded-lg border {{ $priority === 'stat' ? 'border-red-500 bg-red-50 dark:bg-red-900/30' : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800' }} p-4">
                    <input type="radio" wire:model="priority" value="stat" class="sr-only">
                    <div class="flex items-center">
                        <div class="text-sm">
                            <span class="font-medium {{ $priority === 'stat' ? 'text-red-900 dark:text-red-100' : 'text-gray-900 dark:text-white' }}">{{ __('STAT') }}</span>
                            <p class="text-xs {{ $priority === 'stat' ? 'text-red-600 dark:text-red-300' : 'text-gray-500' }}">{{ __('Inmediato') }}</p>
                        </div>
                    </div>
                </label>
            </div>
        </div>

        <!-- Fasting -->
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                {{ __('Ayuno requerido') }}
            </label>
            <select
                wire:model="fasting"
                class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
            >
                <option value="unknown">{{ __('No especificado') }}</option>
                <option value="required">{{ __('Sí, requiere ayuno (8-12 horas)') }}</option>
                <option value="not_required">{{ __('No requiere ayuno') }}</option>
            </select>
        </div>

        <!-- Clinical Notes -->
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                {{ __('Notas clínicas / Diagnóstico presuntivo') }}
            </label>
            <textarea
                wire:model="clinicalNotes"
                rows="3"
                placeholder="{{ __('Indicación clínica, diagnóstico, medicamentos actuales...') }}"
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
                <span wire:loading.remove>{{ __('Crear orden') }}</span>
                <span wire:loading>{{ __('Creando...') }}</span>
            </button>
        </div>
    </form>
</div>