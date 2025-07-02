@extends('layouts.app')

@section('title', 'Detalle de Solicitud #' . $permission->request_number)

@php
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => route('dashboard')],
    ['title' => 'Solicitudes', 'url' => route('permissions.index')],
    ['title' => 'Solicitud #' . $permission->request_number, 'url' => ''],
];
@endphp

@section('content')
<div class="space-y-6" x-data="{ showUploadModal: false }">
    <!-- Header con acciones -->
    <div class="lg:flex lg:items-center lg:justify-between">
        <div class="flex-1 min-w-0">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <span class="inline-flex items-center justify-center w-12 h-12 rounded-full {{ getStatusColor($permission->status) }}">
                        <span class="text-lg font-medium text-white">
                            {{ substr($permission->permissionType->name, 0, 2) }}
                        </span>
                    </span>
                </div>
                <div class="ml-4">
                    <h1 class="text-2xl font-bold text-gray-900">
                        Solicitud #{{ $permission->request_number }}
                    </h1>
                    <div class="flex items-center mt-1 space-x-4">
                        <span class="inline-flex items-center px-3 py-0.5 rounded-full text-sm font-medium {{ getStatusBadgeColor($permission->status) }}">
                            {{ getStatusLabel($permission->status) }}
                        </span>
                        @if($permission->metadata['is_urgent'] ?? false)
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.268 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                            URGENTE
                        </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="mt-5 flex lg:mt-0 lg:ml-4">
            @can('update', $permission)
                @if($permission->status === 'draft')
                <span class="sm:ml-3">
                    <a href="{{ route('permissions.edit', $permission) }}" 
                       class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                        Editar
                    </a>
                </span>
                @endif

                @if($permission->status === 'draft')
                <span class="ml-3">
                    <form action="{{ route('permissions.submit', $permission) }}" method="POST" class="inline-block">
                        @csrf
                        <button type="submit" 
                                onclick="return confirm('¿Está seguro de enviar esta solicitud para aprobación?')"
                                class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                            </svg>
                            Enviar para Aprobación
                        </button>
                    </form>
                </span>
                @endif

                @if(in_array($permission->status, ['draft', 'pending_supervisor', 'pending_hr', 'approved']))
                <span class="ml-3">
                    <form action="{{ route('permissions.cancel', $permission) }}" method="POST" class="inline-block">
                        @csrf
                        <button type="submit" 
                                onclick="return confirm('¿Está seguro de cancelar esta solicitud?')"
                                class="inline-flex items-center px-4 py-2 border border-red-300 rounded-md shadow-sm text-sm font-medium text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            Cancelar
                        </button>
                    </form>
                </span>
                @endif
            @endcan

            @can('approve', $permission)
            <span class="ml-3">
                <a href="{{ route('approvals.show', $permission) }}" 
                   class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Aprobar/Rechazar
                </a>
            </span>
            @endcan
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Información principal -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Detalles del permiso -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Detalles del Permiso</h3>
                </div>
                <div class="px-6 py-4">
                    <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Tipo de Permiso</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $permission->permissionType->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Duración</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $permission->requested_hours }} horas</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Fecha y Hora de Inicio</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $permission->start_datetime->format('d/m/Y H:i') }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Fecha y Hora de Fin</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $permission->end_datetime->format('d/m/Y H:i') }}</dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-sm font-medium text-gray-500">Motivo</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $permission->reason }}</dd>
                        </div>
                        @if($permission->metadata['additional_info'] ?? false)
                        <div class="sm:col-span-2">
                            <dt class="text-sm font-medium text-gray-500">Información Adicional</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $permission->metadata['additional_info'] }}</dd>
                        </div>
                        @endif
                    </dl>
                </div>
            </div>

            <!-- Documentos adjuntos -->
            @if($permission->permissionType->requires_document)
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Documentos Adjuntos</h3>
                        @can('uploadDocument', $permission)
                        <button @click="showUploadModal = true" 
                                class="inline-flex items-center px-3 py-1 border border-transparent text-sm font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Subir Documento
                        </button>
                        @endcan
                    </div>
                </div>
                <div class="px-6 py-4">
                    @if($permission->documents->count() > 0)
                    <ul class="divide-y divide-gray-200">
                        @foreach($permission->documents as $document)
                        <li class="py-3 flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    @if($document->isPdf())
                                    <svg class="h-8 w-8 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    @elseif($document->isImage())
                                    <svg class="h-8 w-8 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    @else
                                    <svg class="h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    @endif
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-gray-900">{{ $document->original_name }}</p>
                                    <p class="text-sm text-gray-500">
                                        {{ $document->document_type_name }} • {{ $document->formatted_size }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                @can('downloadDocument', $permission)
                                <a href="{{ route('permissions.download-document', $document) }}" 
                                   class="text-blue-600 hover:text-blue-900 p-1 rounded-full hover:bg-blue-50">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                </a>
                                @endcan
                                @can('update', $permission)
                                <form action="{{ route('permissions.delete-document', $document) }}" method="POST" class="inline-block">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" 
                                            onclick="return confirm('¿Está seguro de eliminar este documento?')"
                                            class="text-red-600 hover:text-red-900 p-1 rounded-full hover:bg-red-50">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </form>
                                @endcan
                            </div>
                        </li>
                        @endforeach
                    </ul>
                    @else
                    <div class="text-center py-6">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No hay documentos</h3>
                        <p class="mt-1 text-sm text-gray-500">Este tipo de permiso requiere documentos de respaldo.</p>
                        @can('uploadDocument', $permission)
                        <div class="mt-6">
                            <button @click="showUploadModal = true" 
                                    class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                </svg>
                                Subir Primer Documento
                            </button>
                        </div>
                        @endcan
                    </div>
                    @endif
                </div>
            </div>
            @endif

            <!-- Historial de aprobaciones -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Historial de Aprobaciones</h3>
                </div>
                <div class="px-6 py-4">
                    <div class="flow-root">
                        <ul class="-mb-8">
                            @forelse($permission->approvals as $approval)
                            <li>
                                <div class="relative pb-8">
                                    @if(!$loop->last)
                                    <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                                    @endif
                                    <div class="relative flex space-x-3">
                                        <div>
                                            <span class="h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white
                                                {{ $approval->status === 'approved' ? 'bg-green-500' : ($approval->status === 'rejected' ? 'bg-red-500' : 'bg-yellow-500') }}">
                                                @if($approval->status === 'approved')
                                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                                @elseif($approval->status === 'rejected')
                                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                                @else
                                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                                @endif
                                            </span>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div>
                                                <div class="text-sm">
                                                    <span class="font-medium text-gray-900">{{ $approval->approver->full_name }}</span>
                                                    <span class="text-gray-500">{{ $approval->level_name }}</span>
                                                </div>
                                                <p class="mt-0.5 text-sm text-gray-500">
                                                    {{ $approval->approved_at ? $approval->approved_at->format('d/m/Y H:i') : 'Pendiente' }}
                                                </p>
                                            </div>
                                            <div class="mt-2 text-sm text-gray-700">
                                                @if($approval->status === 'pending')
                                                <p class="text-yellow-600 font-medium">Pendiente de aprobación</p>
                                                @else
                                                <p class="capitalize">
                                                    {{ $approval->status === 'approved' ? 'Aprobado' : 'Rechazado' }}
                                                    @if($approval->comments)
                                                    - {{ $approval->comments }}
                                                    @endif
                                                </p>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </li>
                            @empty
                            <li class="text-center py-6">
                                <p class="text-sm text-gray-500">No hay historial de aprobaciones aún.</p>
                            </li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Información del solicitante -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Solicitante</h3>
                </div>
                <div class="px-6 py-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center">
                                <span class="text-sm font-medium text-white">
                                    {{ substr($permission->user->first_name, 0, 1) }}{{ substr($permission->user->last_name, 0, 1) }}
                                </span>
                            </div>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-900">{{ $permission->user->full_name }}</p>
                            <p class="text-sm text-gray-500">{{ $permission->user->email }}</p>
                        </div>
                    </div>
                    <dl class="mt-4 space-y-3">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">DNI</dt>
                            <dd class="text-sm text-gray-900">{{ $permission->user->dni }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Departamento</dt>
                            <dd class="text-sm text-gray-900">{{ $permission->user->department->name ?? 'Sin departamento' }}</dd>
                        </div>
                        @if($permission->user->immediateSupervisor)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Supervisor</dt>
                            <dd class="text-sm text-gray-900">{{ $permission->user->immediateSupervisor->full_name }}</dd>
                        </div>
                        @endif
                    </dl>
                </div>
            </div>

            <!-- Información de la solicitud -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Información</h3>
                </div>
                <div class="px-6 py-4">
                    <dl class="space-y-3">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Número de Solicitud</dt>
                            <dd class="text-sm text-gray-900 font-mono">{{ $permission->request_number }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Fecha de Creación</dt>
                            <dd class="text-sm text-gray-900">{{ $permission->created_at->format('d/m/Y H:i') }}</dd>
                        </div>
                        @if($permission->submitted_at)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Fecha de Envío</dt>
                            <dd class="text-sm text-gray-900">{{ $permission->submitted_at->format('d/m/Y H:i') }}</dd>
                        </div>
                        @endif
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Prioridad</dt>
                            <dd class="text-sm text-gray-900">
                                @switch($permission->metadata['priority'] ?? 2)
                                    @case(1) Alta @break
                                    @case(3) Baja @break
                                    @default Normal
                                @endswitch
                            </dd>
                        </div>
                        @if($permission->isActive())
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Estado Actual</dt>
                            <dd class="text-sm">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <span class="w-1.5 h-1.5 bg-green-400 rounded-full mr-1.5 animate-pulse"></span>
                                    Activo ahora
                                </span>
                            </dd>
                        </div>
                        @endif
                    </dl>
                </div>
            </div>

            <!-- Acciones rápidas -->
            @if($permission->status === 'approved' && $permission->isActive())
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Control Biométrico</h3>
                </div>
                <div class="px-6 py-4">
                    <p class="text-sm text-gray-600 mb-4">Su permiso está activo. Use el sistema biométrico para registrar su salida y entrada.</p>
                    <a href="{{ route('biometric.index') }}" 
                       class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 008 4.07M3 15.364c.64-1.319 1-2.8 1-4.364 0-1.457.39-2.823 1.07-4"></path>
                        </svg>
                        Ir a Control Biométrico
                    </a>
                </div>
            </div>
            @endif
        </div>
    </div>
    <!-- Modal para subir documentos -->
@can('uploadDocument', $permission)
<div x-show="showUploadModal"
     x-transition:enter="ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-50 overflow-y-auto"
     style="display: none;">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
        
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form action="{{ route('permissions.upload-document', $permission) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Subir Documento</h3>
                            
                            <div class="space-y-4">
                                <div>
                                    <label for="document_type" class="block text-sm font-medium text-gray-700">Tipo de Documento</label>
                                    <select id="document_type" name="document_type" required 
                                            class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                                        <option value="">Seleccione el tipo</option>
                                        @foreach($permission->permissionType->getRequiredDocuments() as $docType)
                                        <option value="{{ $docType }}">{{ getDocumentTypeName($docType) }}</option>
                                        @endforeach
                                        <option value="otros">Otros</option>
                                    </select>
                                </div>

                                <div>
                                    <label for="document" class="block text-sm font-medium text-gray-700">Archivo</label>
                                    <input type="file" id="document" name="document" required
                                           accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                                           class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                    <p class="mt-1 text-xs text-gray-500">PDF, JPG, PNG, DOC, DOCX (máx. 5MB)</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" 
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Subir Documento
                    </button>
                    <button @click="showUploadModal = false" type="button" 
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan

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

function getDocumentTypeName($type) {
    return match($type) {
        'certificado_medico' => 'Certificado Médico',
        'certificado_control_medico' => 'Certificado de Control Médico',
        'copia_citacion' => 'Copia de Citación',
        'acreditacion_edil' => 'Acreditación Edil',
        'resolucion_nombramiento' => 'Resolución de Nombramiento',
        'horario_visado' => 'Horario Visado',
        'horario_recuperacion' => 'Horario de Recuperación',
        'partida_nacimiento' => 'Partida de Nacimiento',
        'declaracion_jurada_supervivencia' => 'Declaración Jurada de Supervivencia',
        default => ucwords(str_replace('_', ' ', $type))
    };
}
@endphp
@endsection