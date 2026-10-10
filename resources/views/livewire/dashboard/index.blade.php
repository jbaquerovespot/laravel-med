<x-slot name="header">{{ __('Dashboard') }}</x-slot>

<div class="space-y-6">
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 dark:bg-blue-900 mr-4">
                    <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Pacientes Activos') }}</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $activePatientsCount }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 dark:bg-green-900 mr-4">
                    <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Consultas Hoy') }}</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $todayEncountersCount }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-purple-100 dark:bg-purple-900 mr-4">
                    <svg class="h-6 w-6 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Nuevos (7 días)') }}</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $recentPatientsCount }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('Acciones Rápidas') }}</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @can('patients.create')
            <a href="{{ route('patients.create') }}" class="flex flex-col items-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                <svg class="h-8 w-8 text-blue-600 dark:text-blue-400 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                </svg>
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Nuevo Paciente') }}</span>
            </a>
            @endcan

            @can('patients.view')
            <a href="{{ route('patients.index') }}" class="flex flex-col items-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                <svg class="h-8 w-8 text-green-600 dark:text-green-400 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Buscar Paciente') }}</span>
            </a>
            @endcan

            @can('encounters.create')
            <a href="{{ route('encounters.create') }}" class="flex flex-col items-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                <svg class="h-8 w-8 text-purple-600 dark:text-purple-400 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Nueva Consulta') }}</span>
            </a>
            @endcan

            <a href="#" class="flex flex-col items-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors opacity-50 cursor-not-allowed">
                <svg class="h-8 w-8 text-gray-400 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <span class="text-sm font-medium text-gray-500">{{ __('Reportes') }}</span>
            </a>
        </div>
    </div>

    <!-- Recent Items -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent Patients -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h3 class="font-semibold text-gray-900 dark:text-white">{{ __('Pacientes Recientes') }}</h3>
                <a href="{{ route('patients.index') }}" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">{{ __('Ver todos') }}</a>
            </div>
            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($recentPatients as $patient)
                <a href="{{ route('patients.show', $patient->id) }}" class="block px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">{{ $patient->full_name }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $patient->document_number }}</p>
                        </div>
                        <span class="text-xs text-gray-400">{{ $patient->created_at->diffForHumans() }}</span>
                    </div>
                </a>
                @empty
                <div class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">
                    {{ __('No hay pacientes registrados') }}
                </div>
                @endforelse
            </div>
        </div>

        <!-- Recent Encounters -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h3 class="font-semibold text-gray-900 dark:text-white">{{ __('Consultas Recientes') }}</h3>
            </div>
            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($recentEncounters as $encounter)
                <div class="px-4 py-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">
                                {{ $encounter->patient?->full_name ?? 'Paciente eliminado' }}
                            </p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ $encounter->reason ?? 'Sin motivo' }}
                            </p>
                        </div>
                        <div class="text-right">
                            <span class="px-2 py-1 text-xs rounded-full {{ $encounter->status === 'finished' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-yellow-100 text-yellow-800' }}">
                                {{ $encounter->status }}
                            </span>
                            <p class="text-xs text-gray-400 mt-1">{{ $encounter->created_at->diffForHumans() }}</p>
                        </div>
                    </div>
                </div>
                @empty
                <div class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">
                    {{ __('No hay consultas registradas') }}
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>