<x-slot name="header">{{ __('Editar Paciente') }}</x-slot>

<div class="max-w-4xl mx-auto">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
        <form wire:submit="save" class="p-6 space-y-6">
            <!-- Personal Information -->
            <div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">{{ __('Información Personal') }}</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ __('Número de Documento') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="text" wire:model="document_number"
                            class="block w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white
                                {{ $errors->has('document_number') ? 'border-red-500' : 'border-gray-300 dark:border-gray-600' }}">
                        @error('document_number')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ __('Primer Nombre') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="text" wire:model="first_name"
                            class="block w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white
                                {{ $errors->has('first_name') ? 'border-red-500' : 'border-gray-300 dark:border-gray-600' }}">
                        @error('first_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ __('Apellidos') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="text" wire:model="last_name"
                            class="block w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white
                                {{ $errors->has('last_name') ? 'border-red-500' : 'border-gray-300 dark:border-gray-600' }}">
                        @error('last_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ __('Fecha de Nacimiento') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="date" wire:model="birth_date"
                            class="block w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white
                                {{ $errors->has('birth_date') ? 'border-red-500' : 'border-gray-300 dark:border-gray-600' }}">
                        @error('birth_date')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ __('Género') }} <span class="text-red-500">*</span>
                        </label>
                        <select wire:model="gender"
                            class="block w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white
                                {{ $errors->has('gender') ? 'border-red-500' : 'border-gray-300 dark:border-gray-600' }}">
                            <option value="">{{ __('Seleccionar...') }}</option>
                            <option value="male">{{ __('Masculino') }}</option>
                            <option value="female">{{ __('Femenino') }}</option>
                            <option value="other">{{ __('Otro') }}</option>
                        </select>
                        @error('gender')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Estado Civil') }}</label>
                        <select wire:model="marital_status"
                            class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            <option value="">{{ __('Seleccionar...') }}</option>
                            <option value="single">{{ __('Soltero') }}</option>
                            <option value="married">{{ __('Casado') }}</option>
                            <option value="divorced">{{ __('Divorciado') }}</option>
                            <option value="widowed">{{ __('Viudo') }}</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Contact Information -->
            <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">{{ __('Información de Contacto') }}</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Email') }}</label>
                        <input type="email" wire:model="email"
                            class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Teléfono') }}</label>
                        <input type="tel" wire:model="phone"
                            class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Dirección') }}</label>
                        <input type="text" wire:model="address"
                            class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Ciudad') }}</label>
                        <input type="text" wire:model="city"
                            class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Departamento') }}</label>
                        <input type="text" wire:model="state"
                            class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                </div>
            </div>

            <!-- Emergency Contact -->
            <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">{{ __('Contacto de Emergencia') }}</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Nombre') }}</label>
                        <input type="text" wire:model="emergency_contact_name"
                            class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Teléfono') }}</label>
                        <input type="tel" wire:model="emergency_contact_phone"
                            class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="border-t border-gray-200 dark:border-gray-700 pt-6 flex items-center justify-between">
                <a href="{{ route('patients.show', $patientId) }}" 
                   class="text-red-600 hover:text-red-700 text-sm">
                    {{ __('Cancelar') }}
                </a>
                <button type="submit" wire:loading.attr="disabled"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50">
                    <span wire:loading.remove>{{ __('Guardar Cambios') }}</span>
                    <span wire:loading>{{ __('Guardando...') }}</span>
                </button>
            </div>
        </form>
    </div>
</div>