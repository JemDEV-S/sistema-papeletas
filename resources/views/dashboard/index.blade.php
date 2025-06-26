@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="space-y-6">
    <!-- Mensaje de bienvenida -->
    <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg shadow">
        <div class="px-6 py-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 bg-white bg-opacity-20 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10m0 0V6a2 2 0 00-2-2H9a2 2 0 00-2 2v2m0 0v10a2 2 0 002 2h8a2 2 0 002-2V8M9 12h6"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <h1 class="text-lg font-semibold text-white">
                        ¡Bienvenido, {{ auth()->user()->first_name }}!
                    </h1>
                    <p class="text-blue-100">
                        {{ now()->format('l, j \d\e F \d\e Y') }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Estadísticas principales -->
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
        @if(isset($stats))
            @foreach($stats as $key => $value)
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                                @switch($key)
                                    @case('total_users')
                                    @case('subordinates_count')
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                                        </svg>
                                        @break
                                    @case('pending_requests')
                                    @case('pending_hr_approval')
                                    @case('pending_supervisor_approval')
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        @break
                                    @case('approved_today')
                                    @case('approved_requests')
                                    @case('approved_this_month')
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        @break
                                    @case('total_requests')
                                    @case('requests_this_month')
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        @break
                                    @default
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                        </svg>
                                @endswitch
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">
                                    {{ getStatLabel($key) }}
                                </dt>
                                <dd class="text-lg font-medium text-gray-900">
                                    {{ is_numeric($value) ? number_format($value) : $value }}
                                    @if(str_contains($key, 'hours'))
                                        <span class="text-sm text-gray-500">hrs</span>
                                    @endif
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        @endif
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <!-- Solicitudes recientes / Pendientes de aprobación -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    @if(auth()->user()->hasRole('empleado'))
                        Mis Solicitudes Recientes
                    @else
                        Solicitudes Pendientes
                    @endif
                </h3>
            </div>
            <div class="px-6 py-4">
                @if(isset($recent_requests) && count($recent_requests) > 0)
                <div class="space-y-3">
                    @foreach($recent_requests as $request)
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0">
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full {{ getStatusColor($request->status) }}">
                                    <span class="text-xs font-medium text-white">
                                        {{ substr($request->permissionType->name, 0, 2) }}
                                    </span>
                                </span>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900">
                                    {{ $request->permissionType->name }}
                                </p>
                                <p class="text-xs text-gray-500">
                                    @if(!auth()->user()->hasRole('empleado'))
                                        {{ $request->user->full_name }} •
                                    @endif
                                    {{ $request->start_datetime->format('d/m/Y H:i') }}
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ getStatusBadgeColor($request->status) }}">
                                {{ getStatusLabel($request->status) }}
                            </span>
                            <a href="{{ route('permissions.show', $request) }}" 
                               class="text-blue-600 hover:text-blue-900">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </a>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="text-center py-6">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No hay solicitudes</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        @if(auth()->user()->hasRole('empleado'))
                            Comienza creando tu primera solicitud de permiso.
                        @else
                            No hay solicitudes pendientes de aprobación.
                        @endif
                    </p>
                    @if(auth()->user()->hasRole('empleado'))
                    <div class="mt-6">
                        <a href="{{ route('permissions.create') }}" 
                           class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Nueva Solicitud
                        </a>
                    </div>
                    @endif
                </div>
                @endif
            </div>
        </div>

        <!-- Saldos de permisos (para empleados) o Gráfico de actividad -->
        @if(auth()->user()->hasRole('empleado') && isset($permission_balances))
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Mis Saldos de Permisos</h3>
            </div>
            <div class="px-6 py-4">
                @if(count($permission_balances) > 0)
                <div class="space-y-4">
                    @foreach($permission_balances as $balance)
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-900">{{ $balance->permissionType->name }}</p>
                            <p class="text-xs text-gray-500">
                                {{ $balance->used_hours }} / {{ $balance->available_hours }} horas usadas
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-medium text-gray-900">{{ $balance->remaining_hours }} hrs</p>
                            <div class="w-20 bg-gray-200 rounded-full h-2">
                                <div class="h-2 rounded-full {{ $balance->usage_percentage > 80 ? 'bg-red-600' : ($balance->usage_percentage > 60 ? 'bg-yellow-600' : 'bg-green-600') }}" 
                                     style="width: {{ min($balance->usage_percentage, 100) }}%"></div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="text-center py-6">
                    <p class="text-sm text-gray-500">No hay saldos de permisos para mostrar</p>
                </div>
                @endif
            </div>
        </div>
        @else
        <!-- Gráfico de actividad -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Actividad Reciente</h3>
            </div>
            <div class="px-6 py-4">
                <canvas id="activityChart" width="400" height="200"></canvas>
            </div>
        </div>
        @endif
    </div>

    <!-- Próximos permisos (para empleados) -->
    @if(auth()->user()->hasRole('empleado') && isset($upcoming_permissions) && count($upcoming_permissions) > 0)
    <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Próximos Permisos</h3>
        </div>
        <div class="px-6 py-4">
            <div class="space-y-3">
                @foreach($upcoming_permissions as $permission)
                <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg border border-blue-200">
                    <div class="flex items-center space-x-3">
                        <div class="flex-shrink-0">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900">{{ $permission->permissionType->name }}</p>
                            <p class="text-xs text-gray-600">
                                {{ $permission->start_datetime->format('d/m/Y H:i') }} - {{ $permission->end_datetime->format('H:i') }}
                            </p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-blue-600 font-medium">
                            {{ $permission->start_datetime->diffForHumans() }}
                        </p>
                        <p class="text-xs text-gray-500">{{ $permission->requested_hours }} hrs</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    <!-- Alertas de saldos bajos (para RRHH) -->
    @if(auth()->user()->hasRole('jefe_rrhh') && isset($balance_alerts) && count($balance_alerts) > 0)
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg">
        <div class="px-6 py-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="w-5 h-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.268 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-yellow-800">Alertas de Saldos Bajos</h3>
                    <div class="mt-2 text-sm text-yellow-700">
                        <p>Los siguientes empleados tienen saldos de permisos bajos:</p>
                        <ul class="mt-1 space-y-1">
                            @foreach(array_slice($balance_alerts, 0, 3) as $alert)
                            <li>• {{ $alert['user'] }} - {{ $alert['permission_type'] }} ({{ $alert['remaining_hours'] }}h restantes)</li>
                            @endforeach
                            @if(count($balance_alerts) > 3)
                            <li>• y {{ count($balance_alerts) - 3 }} más...</li>
                            @endif
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

@php
function getStatLabel($key) {
    return match($key) {
        'total_users' => 'Total Usuarios',
        'subordinates_count' => 'Mi Equipo',
        'pending_requests' => 'Pendientes',
        'pending_hr_approval' => 'Pendientes RRHH',
        'pending_supervisor_approval' => 'Pendientes Mi Aprobación',
        'approved_today' => 'Aprobadas Hoy',
        'approved_requests' => 'Aprobadas',
        'approved_this_month' => 'Aprobadas Este Mes',
        'total_requests' => 'Total Solicitudes',
        'requests_this_month' => 'Solicitudes Este Mes',
        'hours_used_this_month' => 'Horas Usadas',
        'team_hours_used' => 'Horas Equipo',
        'total_hours_approved' => 'Horas Aprobadas',
        default => ucwords(str_replace('_', ' ', $key))
    };
}

function getStatusColor($status) {
    return match($status) {
        'draft' => 'bg-gray-500',
        'pending_supervisor' => 'bg-yellow-500',
        'pending_hr' => 'bg-blue-500',
        'approved' => 'bg-green-500',
        'rejected' => 'bg-red-500',
        'in_execution' => 'bg-purple-500',
        'completed' => 'bg-green-600',
        'cancelled' => 'bg-gray-600',
        default => 'bg-gray-500'
    };
}

function getStatusBadgeColor($status) {
    return match($status) {
        'draft' => 'bg-gray-100 text-gray-800',
        'pending_supervisor' => 'bg-yellow-100 text-yellow-800',
        'pending_hr' => 'bg-blue-100 text-blue-800',
        'approved' => 'bg-green-100 text-green-800',
        'rejected' => 'bg-red-100 text-red-800',
        'in_execution' => 'bg-purple-100 text-purple-800',
        'completed' => 'bg-green-100 text-green-800',
        'cancelled' => 'bg-gray-100 text-gray-800',
        default => 'bg-gray-100 text-gray-800'
    };
}

function getStatusLabel($status) {
    return match($status) {
        'draft' => 'Borrador',
        'pending_supervisor' => 'Pendiente Jefe',
        'pending_hr' => 'Pendiente RRHH',
        'approved' => 'Aprobado',
        'rejected' => 'Rechazado',
        'in_execution' => 'En Ejecución',
        'completed' => 'Completado',
        'cancelled' => 'Cancelado',
        default => ucfirst($status)
    };
}
@endphp
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    @if(isset($chart_data))
    // Gráfico de actividad
    const ctx = document.getElementById('activityChart').getContext('2d');
    const chartData = @json($chart_data);
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartData.map(item => item.day),
            datasets: [{
                label: 'Solicitudes',
                data: chartData.map(item => item.requests),
                borderColor: 'rgb(59, 130, 246)',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                tension: 0.1
            }, {
                label: 'Aprobadas',
                data: chartData.map(item => item.approved),
                borderColor: 'rgb(34, 197, 94)',
                backgroundColor: 'rgba(34, 197, 94, 0.1)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                },
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    @endif
});
</script>
@endpush