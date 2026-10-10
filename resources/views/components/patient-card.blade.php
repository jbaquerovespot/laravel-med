@props(['patient'])

<div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
    <!-- Header with blood type badge -->
    <div class="px-4 py-3 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center">
                    <span class="text-sm font-semibold text-blue-600 dark:text-blue-400">
                        {{ strtoupper(substr($patient->first_name, 0, 1) . substr($patient->last_name, 0, 1)) }}
                    </span>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-900 dark:text-white">
                        {{ $patient->full_name }}
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $patient->document_number }}
                    </p>
                </div>
            </div>
            
            @if($patient->blood_type ?? null)
            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300">
                {{ $patient->blood_type }}{{ $patient->rh_factor ?? '' }}
            </span>
            @endif
        </div>
    </div>
    
    <!-- Body -->
    <div class="p-4">
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <p class="text-gray-500 dark:text-gray-400">{{ __('Fecha de Nacimiento') }}</p>
                <p class="font-medium text-gray-900 dark:text-white">
                    {{ $patient->birth_date?->format('d/m/Y') ?? '-' }}
                    @if($patient->birth_date)
                    <span class="text-gray-400">({{ $patient->birth_date->age }} años)</span>
                    @endif
                </p>
            </div>
            <div>
                <p class="text-gray-500 dark:text-gray-400">{{ __('Género') }}</p>
                <p class="font-medium text-gray-900 dark:text-white">
                    {{ $patient->gender === 'male' ? 'Masculino' : ($patient->gender === 'female' ? 'Femenino' : '-') }}
                </p>
            </div>
            @if($patient->phone)
            <div>
                <p class="text-gray-500 dark:text-gray-400">{{ __('Teléfono') }}</p>
                <p class="font-medium text-gray-900 dark:text-white">{{ $patient->phone }}</p>
            </div>
            @endif
            @if($patient->email)
            <div>
                <p class="text-gray-500 dark:text-gray-400">{{ __('Email') }}</p>
                <p class="font-medium text-gray-900 dark:text-white">{{ $patient->email }}</p>
            </div>
            @endif
        </div>
        
        @if($patient->emergency_contact_name ?? null)
        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">{{ __('Contacto de Emergencia') }}</p>
            <p class="text-sm text-gray-900 dark:text-white">
                {{ $patient->emergency_contact_name }}
                @if($patient->emergency_contact_phone)
                - {{ $patient->emergency_contact_phone }}
                @endif
            </p>
        </div>
        @endif
    </div>
    
    <!-- Actions -->
    <div class="px-4 py-3 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between">
            <span class="text-xs text-gray-500 dark:text-gray-400">
                {{ __('Creado') }}: {{ $patient->created_at->diffForHumans() }}
            </span>
            <a href="{{ route('patients.show', $patient->id) }}" 
               class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
                {{ __('Ver detalles') }} →
            </a>
        </div>
    </div>
</div>