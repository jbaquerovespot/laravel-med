<x-slot name="header">
    {{ __('Consulta') }} #{{ $encounter->id }}
    <span class="text-gray-500 font-normal text-base ml-2">
        {{ $encounter->patient?->full_name ?? 'Paciente no disponible' }}
    </span>
</x-slot>

<div class="max-w-5xl mx-auto space-y-6">
    <!-- Header Info -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-start justify-between">
            <div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                    {{ $encounter->patient?->full_name ?? 'Paciente no disponible' }}
                </h2>
                <p class="text-gray-500">{{ $encounter->patient?->document_number ?? '-' }}</p>
            </div>
            <div class="text-right">
                <p class="text-sm text-gray-500">{{ $encounter->created_at->format('d/m/Y H:i') }}</p>
                <span class="px-2 py-1 text-xs rounded-full {{ $encounter->status === 'finished' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                    {{ $encounter->status === 'finished' ? 'Finalizada' : 'En progreso' }}
                </span>
            </div>
        </div>
    </div>

    <!-- Reason -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="font-semibold text-gray-900 dark:text-white mb-2">{{ __('Motivo de Consulta') }}</h3>
        <p class="text-gray-700 dark:text-gray-300">{{ $encounter->reason ?? 'No registrado' }}</p>
        @if($encounter->note)
        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
            <h4 class="text-sm font-medium text-gray-500 mb-2">{{ __('Anamnesis') }}</h4>
            <p class="text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ $encounter->note }}</p>
        </div>
        @endif
    </div>

    <!-- Vital Signs -->
    @if($encounter->observations->where('category', 'vital-signs')->count() > 0)
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="font-semibold text-gray-900 dark:text-white mb-4">{{ __('Signos Vitales') }}</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @foreach($encounter->observations->where('category', 'vital-signs') as $obs)
            <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $obs->code ?? 'Código' }}</p>
                <p class="text-lg font-semibold text-gray-900 dark:text-white">
                    {{ $obs->value }} {{ $obs->unit }}
                </p>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Diagnoses -->
    @if($encounter->conditions->count() > 0)
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="font-semibold text-gray-900 dark:text-white mb-4">{{ __('Diagnósticos') }}</h3>
        <table class="w-full">
            <thead>
                <tr class="border-b border-gray-200 dark:border-gray-700">
                    <th class="text-left py-2 text-sm text-gray-500">{{ __('Código') }}</th>
                    <th class="text-left py-2 text-sm text-gray-500">{{ __('Descripción') }}</th>
                    <th class="text-left py-2 text-sm text-gray-500">{{ __('Tipo') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($encounter->conditions as $condition)
                <tr class="border-b border-gray-100 dark:border-gray-700">
                    <td class="py-2 font-medium text-gray-900 dark:text-white">{{ $condition->code ?? '-' }}</td>
                    <td class="py-2 text-gray-700 dark:text-gray-300">{{ $condition->note ?? '-' }}</td>
                    <td class="py-2">
                        <span class="px-2 py-1 text-xs rounded {{ $condition->category === 'principal' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-600' }}">
                            {{ $condition->category === 'principal' ? 'Principal' : 'Secundario' }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Medications -->
    @if($encounter->medicationRequests->count() > 0)
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="font-semibold text-gray-900 dark:text-white mb-4">{{ __('Medicamentos Prescritos') }}</h3>
        <table class="w-full">
            <thead>
                <tr class="border-b border-gray-200 dark:border-gray-700">
                    <th class="text-left py-2 text-sm text-gray-500">{{ __('Medicamento') }}</th>
                    <th class="text-left py-2 text-sm text-gray-500">{{ __('Dosis') }}</th>
                    <th class="text-left py-2 text-sm text-gray-500">{{ __('Frecuencia') }}</th>
                    <th class="text-left py-2 text-sm text-gray-500">{{ __('Duración') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($encounter->medicationRequests as $med)
                <tr class="border-b border-gray-100 dark:border-gray-700">
                    <td class="py-2 font-medium text-gray-900 dark:text-white">{{ $med->medication ?? '-' }}</td>
                    <td class="py-2 text-gray-700 dark:text-gray-300">{{ $med->dosage ?? '-' }}</td>
                    <td class="py-2 text-gray-700 dark:text-gray-300">{{ $med->frequency ?? '-' }}</td>
                    <td class="py-2 text-gray-700 dark:text-gray-300">{{ $med->duration ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Actions -->
    <div class="flex items-center justify-between">
        <a href="{{ route('patients.show', $encounter->patient_id) }}" 
           class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">
            ← {{ __('Volver al paciente') }}
        </a>
        @can('rda.generate')
        <a href="{{ route('rda.generate', $encounter->id) }}" target="_blank"
           class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center">
            <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            {{ __('Descargar RDA') }}
        </a>
        @endcan
    </div>
</div>