<x-slot name="header">
    {{ $patient->full_name }}
    <span class="text-gray-500 font-normal text-base ml-2">{{ $patient->document_number }}</span>
</x-slot>

<div class="max-w-6xl mx-auto space-y-6">
    <!-- Patient Header -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-start justify-between">
            <div class="flex items-center space-x-4">
                <div class="w-16 h-16 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center">
                    <span class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                        {{ strtoupper(substr($patient->first_name, 0, 1) . substr($patient->last_name, 0, 1)) }}
                    </span>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $patient->full_name }}</h1>
                    <p class="text-gray-500 dark:text-gray-400">{{ $patient->document_number }}</p>
                </div>
            </div>
            
            <div class="flex items-center space-x-2">
                @can('patients.edit')
                <a href="{{ route('patients.edit', $patient->id) }}" 
                   class="px-4 py-2 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600">
                    {{ __('Editar') }}
                </a>
                @endcan
                @can('encounters.create')
                <a href="{{ route('encounters.create', ['patient' => $patient->id]) }}" 
                   class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    {{ __('Nueva Consulta') }}
                </a>
                @endcan
            </div>
        </div>

        <!-- Quick Info -->
        <div class="mt-6 grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="p-3 bg-gray-50 dark:bg-gray-900/50 rounded-lg">
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Edad') }}</p>
                <p class="text-lg font-semibold text-gray-900 dark:text-white">
                    {{ $patient->birth_date?->age ?? '-' }} años
                </p>
            </div>
            <div class="p-3 bg-gray-50 dark:bg-gray-900/50 rounded-lg">
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Fecha de Nacimiento') }}</p>
                <p class="text-lg font-semibold text-gray-900 dark:text-white">
                    {{ $patient->birth_date?->format('d/m/Y') ?? '-' }}
                </p>
            </div>
            <div class="p-3 bg-gray-50 dark:bg-gray-900/50 rounded-lg">
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Género') }}</p>
                <p class="text-lg font-semibold text-gray-900 dark:text-white">
                    {{ $patient->gender === 'male' ? 'Masculino' : ($patient->gender === 'female' ? 'Femenino' : 'Otro') }}
                </p>
            </div>
            @if($patient->phone)
            <div class="p-3 bg-gray-50 dark:bg-gray-900/50 rounded-lg">
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Teléfono') }}</p>
                <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $patient->phone }}</p>
            </div>
            @endif
        </div>
    </div>

    <!-- Allergies Warning -->
    @if($allergies->count() > 0)
    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-4">
        <div class="flex items-center">
            <svg class="w-5 h-5 text-red-600 dark:text-red-400 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <span class="font-medium text-red-800 dark:text-red-200">{{ __('ALERTA: Alérgico a:') }}</span>
        </div>
        <div class="mt-2 flex flex-wrap gap-2">
            @foreach($allergies as $allergy)
            <span class="px-2 py-1 bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200 text-sm rounded">
                {{ $allergy->code ?? $allergy->note ?? 'Desconocido' }}
            </span>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Tabs Content -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Recent Encounters -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="font-semibold text-gray-900 dark:text-white">{{ __('Consultas Recientes') }}</h3>
                </div>
                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($encounters as $encounter)
                    <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">
                                    {{ $encounter->created_at->format('d/m/Y') }}
                                </p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $encounter->reason ?? 'Sin motivo registrado' }}
                                </p>
                            </div>
                            <span class="px-2 py-1 text-xs rounded-full {{ $encounter->status === 'finished' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-yellow-100 text-yellow-800' }}">
                                {{ $encounter->status }}
                            </span>
                        </div>
                    </div>
                    @empty
                    <div class="p-4 text-center text-gray-500 dark:text-gray-400">
                        {{ __('No hay consultas registradas') }}
                    </div>
                    @endforelse
                </div>
            </div>

            <!-- Active Conditions -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="font-semibold text-gray-900 dark:text-white">{{ __('Diagnósticos Activos') }}</h3>
                </div>
                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($conditions as $condition)
                    <div class="p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">
                                    {{ $condition->code ?? '-' }} - {{ $condition->note ?? '-' }}
                                </p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ __('Desde') }}: {{ $condition->created_at->format('d/m/Y') }}
                                </p>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="p-4 text-center text-gray-500 dark:text-gray-400">
                        {{ __('No hay diagnósticos activos') }}
                    </div>
                    @endforelse
                </div>
            </div>

            <!-- Active Medications -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="font-semibold text-gray-900 dark:text-white">{{ __('Medicamentos Activos') }}</h3>
                </div>
                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($medications as $med)
                    <div class="p-4">
                        <p class="font-medium text-gray-900 dark:text-white">
                            {{ $med->medication ?? '-' }}
                        </p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $med->dosage ?? '-' }} - {{ $med->frequency ?? '-' }}
                        </p>
                    </div>
                    @empty
                    <div class="p-4 text-center text-gray-500 dark:text-gray-400">
                        {{ __('No hay medicamentos activos') }}
                    </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div class="space-y-6">
            <!-- Contact Info -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                <h3 class="font-semibold text-gray-900 dark:text-white mb-3">{{ __('Contacto') }}</h3>
                <div class="space-y-3 text-sm">
                    @if($patient->phone)
                    <div class="flex items-center text-gray-600 dark:text-gray-400">
                        <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                        </svg>
                        {{ $patient->phone }}
                    </div>
                    @endif
                    @if($patient->email)
                    <div class="flex items-center text-gray-600 dark:text-gray-400">
                        <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        {{ $patient->email }}
                    </div>
                    @endif
                    @if($patient->address)
                    <div class="flex items-start text-gray-600 dark:text-gray-400">
                        <svg class="w-4 h-4 mr-2 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span>{{ $patient->address }}{{ $patient->city ? ', ' . $patient->city : '' }}</span>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Emergency Contact -->
            @if($patient->emergency_contact_name)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                <h3 class="font-semibold text-gray-900 dark:text-white mb-3">{{ __('Contacto de Emergencia') }}</h3>
                <p class="text-sm text-gray-900 dark:text-white">{{ $patient->emergency_contact_name }}</p>
                @if($patient->emergency_contact_phone)
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $patient->emergency_contact_phone }}</p>
                @endif
            </div>
            @endif
        </div>
    </div>
</div>