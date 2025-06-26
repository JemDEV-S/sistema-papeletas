@extends('layouts.app')

@section('title', 'Mis Solicitudes de Permisos')

@php
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => route('dashboard')],
    ['title' => 'Solicitudes de Permisos', 'url' => ''],
];
@endphp

@section('content')
<div class="space-y-6">
    <!-- Header con acciones -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">
                @if(auth()->user()->hasRole('empleado'))
                    Mis Solicitudes de Permisos
                @else
                    Solicitudes de Permisos
                @endif
            </h1>
            <p class="mt-2 text-sm text-gray-700">
                @if(auth()->user()->hasRole('empleado'))
                    Gestiona tus solicitudes de permisos laborales
                @else
                    Lista de todas las solicitudes de permisos del equipo
                @endif
            </p>
        </div>
        @if(auth()->user()->hasPermission('create_permission_request'))
        <div class="mt-4 sm:mt-0">
            <a href="{{ route('permissions.create') }}" 
               class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Nueva Solicitud
            </a>
        </div>
        @endif
    </div>

    <!-- Filtros -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4">
            <form method="GET" action="{{ route('permissions.index') }}" class="space-y-4 sm:space-y-0 sm:grid sm:grid-cols-2 md:grid-cols-4 lg:grid-cols-6 sm:gap-4">
                <!-- Filtro por estado -->
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700">Estado</label>
                    <select name="status" id="status" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                        <option value="">Todos los estados</option>
                        <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Borrador</option>
                        <option value="pending_supervisor" {{ request('status') === 'pending_supervisor' ? 'selected' : '' }}>Pendiente Jefe</option>
                        <option value="pending_hr" {{ request('status') === 'pending_hr' ? 'selected' : '' }}>Pendiente RRHH</option>
                        <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Aprobado</option>
                        <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rechazado</option>
                        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completado</option>
                    </select>
                </div>

                <!-- Filtro por tipo de permiso -->
                <div>
                    <label for="permission_type" class="block text-sm font-medium text-gray-700">Tipo de Permiso</label>
                    <select name="permission_type" id="permission_type" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                        <option value="">Todos los tipos</option>
                        @foreach($permissionTypes as $type)
                        <option value="{{ $type->id }}" {{ request('permission_type') == $type->id ? 'selected' : '' }}>
                            {{ $type->name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <!-- Filtro fecha desde -->
                <div>
                    <label for="date_from" class="block text-sm font-medium text-gray-700">Desde</label>
                    <input type="date" name="date_from" id="date_from" value="{{ request('date_from') }}"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>

                <!-- Filtro fecha hasta -->
                <div>
                    <label for="date_to" class="block text-sm font-medium text-gray-700">Hasta</label>
                    <input type="date" name="date_to" id="date_to" value="{{ request('date_to') }}"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>

                <!-- Búsqueda -->
                <div class="sm:col-span-2">
                    <label for="search" class="block text-sm font-medium text-gray-700">Buscar</label>
                    <input type="text" name="search" id="search" value="{{ request('search') }}" 
                           placeholder="Número, motivo, empleado..."
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>

                <!-- Botones de acción -->
                <div class="flex items-end space-x-2 sm:col-span-full lg:col-span-6">
                    <button type="submit" 
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        Filtrar
                    </button>
                    <a href="{{ route('permissions.index') }}" 
                       class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Limpiar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Lista de solicitudes -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
        @if($requests->count() > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Solicitud
                        </th>
                        @if(!auth()->user()->hasRole('empleado'))
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Empleado
                        </th>
                        @endif
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Tipo de Permiso
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Período
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Estado
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Fecha Solicitud
                        </th>
                        <th scope="col" class="relative px-6 py-3">
                            <span class="sr-only">Acciones</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($requests as $request)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full {{ getStatusColor($request->status) }}">
                                        <span class="text-xs font-medium text-white">
                                            {{ substr($request->permissionType->name, 0, 2) }}
                                        </span>
                                    </span>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ $request->request_number }}
                                    </div>
                                    @if($request->metadata['is_urgent'] ?? false)
                                    <div class="text-xs text-red-600 font-medium">
                                        <svg class="inline w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.268 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                        </svg>
                                        URGENTE
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        @if(!auth()->user()->hasRole('empleado'))
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $request->user->full_name }}</div>
                            <div class="text-sm text-gray-500">{{ $request->user->department->name ?? 'Sin departamento' }}</div>
                        </td>
                        @endif
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-900">{{ $request->permissionType->name }}</div>
                            <div class="text-sm text-gray-500 max-w-xs truncate">{{ $request->reason }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">
                                {{ $request->start_datetime->format('d/m/Y H:i') }}
                            </div>
                            <div class="text-sm text-gray-500">
                                hasta {{ $request->end_datetime->format('H:i') }}
                                <span class="text-blue-600 font-medium">({{ $request->requested_hours }}h)</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ getStatusBadgeColor($request->status) }}">
                                {{ getStatusLabel($request->status) }}
                            </span>
                            @if($request->approvals->count() > 0)
                            <div class="mt-1 text-xs text-gray-500">
                                Último: {{ $request->approvals->last()->approver->full_name }}
                            </div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $request->created_at->format('d/m/Y') }}
                            <div class="text-xs text-gray-400">
                                {{ $request->created_at->diffForHumans() }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex items-center justify-end space-x-2">
                                <!-- Ver detalles -->
                                <a href="{{ route('permissions.show', $request) }}" 
                                   class="text-blue-600 hover:text-blue-900 p-1 rounded-full hover:bg-blue-50"
                                   title="Ver detalles">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                </a>

                                <!-- Editar (solo si es borrador y es del usuario) -->
                                @if($request->status === 'draft' && $request->user_id === auth()->id())
                                <a href="{{ route('permissions.edit', $request) }}" 
                                   class="text-yellow-600 hover:text-yellow-900 p-1 rounded-full hover:bg-yellow-50"
                                   title="Editar">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </a>
                                @endif

                                <!-- Aprobar (si tiene permisos) -->
                                @if(
                                    ($request->status === 'pending_supervisor' && auth()->user()->hasPermission('approve_level_1') && auth()->user()->subordinates()->where('id', $request->user_id)->exists()) ||
                                    ($request->status === 'pending_hr' && auth()->user()->hasPermission('approve_level_2'))
                                )
                                <a href="{{ route('approvals.show', $request) }}" 
                                   class="text-green-600 hover:text-green-900 p-1 rounded-full hover:bg-green-50"
                                   title="Aprobar/Rechazar">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </a>
                                @endif

                                <!-- Menú de acciones -->
                                <div class="relative" x-data="{ open: false }">
                                    <button @click="open = !open" 
                                            class="text-gray-400 hover:text-gray-600 p-1 rounded-full hover:bg-gray-50">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
                                        </svg>
                                    </button>

                                    <div x-show="open" 
                                         @click.away="open = false"
                                         x-transition:enter="transition ease-out duration-100"
                                         x-transition:enter-start="transform opacity-0 scale-95"
                                         x-transition:enter-end="transform opacity-100 scale-100"
                                         x-transition:leave="transition ease-in duration-75"
                                         x-transition:leave-start="transform opacity-100 scale-100"
                                         x-transition:leave-end="transform opacity-0 scale-95"
                                         class="absolute right-0 z-10 mt-2 w-48 bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5">
                                        <div class="py-1">
                                            @if($request->user_id === auth()->id() && $request->status === 'draft')
                                            <form action="{{ route('permissions.submit', $request) }}" method="POST" class="block">
                                                @csrf
                                                <button type="submit" 
                                                        onclick="return confirm('¿Está seguro de enviar esta solicitud para aprobación?')"
                                                        class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                    Enviar para aprobación
                                                </button>
                                            </form>
                                            @endif

                                            @if($request->user_id === auth()->id() && in_array($request->status, ['draft', 'pending_supervisor', 'pending_hr', 'approved']))
                                            <form action="{{ route('permissions.cancel', $request) }}" method="POST" class="block">
                                                @csrf
                                                <button type="submit" 
                                                        onclick="return confirm('¿Está seguro de cancelar esta solicitud?')"
                                                        class="block w-full text-left px-4 py-2 text-sm text-red-700 hover:bg-red-50">
                                                    Cancelar solicitud
                                                </button>
                                            </form>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Paginación -->
        <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
            {{ $requests->withQueryString()->links() }}
        </div>
        @else
        <!-- Estado vacío -->
        <div class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No se encontraron solicitudes</h3>
            <p class="mt-1 text-sm text-gray-500">
                @if(request()->anyFilled(['status', 'permission_type', 'date_from', 'date_to', 'search']))
                    Intenta ajustar los filtros de búsqueda.
                @else
                    @if(auth()->user()->hasRole('empleado'))
                        Comienza creando tu primera solicitud de permiso.
                    @else
                        No hay solicitudes de permisos para mostrar.
                    @endif
                @endif
            </p>
            @if(auth()->user()->hasPermission('create_permission_request') && !request()->anyFilled(['status', 'permission_type', 'date_from', 'date_to', 'search']))
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

@php
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