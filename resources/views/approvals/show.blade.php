@extends('layouts.app')

@section('title', 'Aprobar Solicitud #' . $permission->request_number)

@php
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => route('dashboard')],
    ['title' => 'Aprobaciones', 'url' => route('approvals.index')],
    ['title' => 'Solicitud #' . $permission->request_number, 'url' => ''],
];
@endphp

@section('content')
<div x-data="approvalForm()" class="space-y-6">
    <!-- Header -->
    <div class="lg:flex lg:items-center lg:justify-between">
        <div class="flex-1 min-w-0">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <span class="inline-flex items-center justify-center w-12 h-12 rounded-full {{ $this->getStatusColor($permission->status) }}">
                        <span class="text-lg font-medium text-white">
                            {{ substr($permission->permissionType->name, 0, 2) }}
                        </span>
                    </span>
                </div>
                <div class="ml-4">
                    <h1 class="text-2xl font-bold text-gray-900">
                        Aprobar Solicitud #{{ $permission->request_number }}
                    </h1>
                    <div class="flex items-center mt-1 space-x-4">
                        <span class="inline-flex items-center px-3 py-0.5 rounded-full text-sm font-medium {{ $this->getStatusBadgeColor($permission->status) }}">
                            {{ $this->getStatusLabel($permission->status) }}
                        </span>
                        @if($permission->metadata['is_urgent'] ?? false)
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.268 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                            URGENTE
                        </span>
                        @endif
                        <span class="text-sm text-gray-500">
                            Nivel de aprobación: <strong>{{ $approvalLevel === 1 ? 'Jefe Inmediato' : 'Recursos Humanos' }}</strong>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Información principal -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Alerta de urgencia -->
            @if($permission->metadata['is_urgent'] ?? false)
            <div class="bg-red-50 border border-red-200 rounded-md p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.268 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">Solicitud Urgente</h3>
                        <div class="mt-2 text-sm text-red-700">
                            <p>Esta solicitud ha sido marcada como urgente y requiere atención prioritaria.</p>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Información del permiso -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Detalles del Permiso</h3>
                </div>
                <div class="px-6 py-4">
                    <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Tipo de Permiso</dt>
                            <dd class="mt-1 text-sm text-gray-900 font-medium">{{ $permission->permissionType->name }}</dd>
                            @if($permission->permissionType->description)
                            <dd class="mt-1 text-xs text-gray-500">{{ $permission->permissionType->description }}</dd>
                            @endif
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Duración Solicitada</dt>
                            <dd class="mt-1 text-sm text-gray-900 font-medium">{{ $permission->requested_hours }} horas</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Fecha y Hora de Inicio</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $permission->start_datetime->format('l, d \d\e F \d\e Y') }}</dd>
                            <dd class="text-sm text-blue-600 font-medium">{{ $permission->start_datetime->format('H:i') }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Fecha y Hora de Fin</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $permission->end_datetime->format('l, d \d\e F \d\e Y') }}</dd>
                            <dd class="text-sm text-blue-600 font-medium">{{ $permission->end_datetime->format('H:i') }}</dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-sm font-medium text-gray-500">Motivo del Permiso</dt>
                            <dd class="mt-1 text-sm text-gray-900 bg-gray-50 p-3 rounded-md">{{ $permission->reason }}</dd>
                        </div>
                        @if($permission->metadata['additional_info'] ?? false)
                        <div class="sm:col-span-2">
                            <dt class="text-sm font-medium text-gray-500">Información Adicional</dt>
                            <dd class="mt-1 text-sm text-gray-900 bg-gray-50 p-3 rounded-md">{{ $permission->metadata['additional_info'] }}</dd>
                        </div>
                        @endif
                    </dl>
                </div>
            </div>

            <!-- Restricciones y validaciones -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg">
                <div class="px-6 py-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-blue-800">Información de Validación</h3>
                            <div class="mt-2 text-sm text-blue-700">
                                <div class="space-y-2">
                                    @if($permission->permissionType->max_hours_per_day)
                                    <p>• Máximo permitido por día: <strong>{{ $permission->permissionType->max_hours_per_day }} horas</strong></p>
                                    @endif
                                    @if($permission->permissionType->max_hours_per_month)
                                    <p>• Máximo permitido por mes: <strong>{{ $permission->permissionType->max_hours_per_month }} horas</strong></p>
                                    @endif
                                    @if($permission->permissionType->max_times_per_month)
                                    <p>• Máximo de veces por mes: <strong>{{ $permission->permissionType->max_times_per_month }}</strong></p>
                                    @endif
                                    <p>• Tiempo de solicitud: <strong>{{ $permission->start_datetime->diffForHumans() }}</strong></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Documentos adjuntos -->
            @if($permission->documents->count() > 0)
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Documentos Adjuntos</h3>
                </div>
                <div class="px-6 py-4">
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
                                <a href="{{ route('permissions.download-document', $document) }}" 
                                   class="text-blue-600 hover:text-blue-900 p-2 rounded-full hover:bg-blue-50">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                </a>
                                <a href="{{ route('permissions.show', $permission) }}#documents" 
                                   target="_blank"
                                   class="text-gray-600 hover:text-gray-900 p-2 rounded-full hover:bg-gray-50">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                </a>
                            </div>
                        </li>
                        @endforeach
                    </ul>
                </div>
            </div>
            @elseif($permission->permissionType->requires_document)
            <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.268 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800">Documentos Requeridos Faltantes</h3>
                        <div class="mt-2 text-sm text-yellow-700">
                            <p>Este tipo de permiso requiere documentos de respaldo que aún no han sido subidos.</p>
                            <p class="mt-1 font-medium">Considere solicitar al empleado que adjunte los documentos necesarios antes de aprobar.</p>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Formulario de aprobación -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Decisión de Aprobación</h3>
                </div>
                <div class="px-6 py-4">
                    <form @submit.prevent="submitDecision()">
                        @csrf
                        
                        <!-- Decisión -->
                        <div class="space-y-4">
                            <fieldset>
                                <legend class="text-sm font-medium text-gray-900">Su Decisión</legend>
                                <div class="mt-2 space-y-2">
                                    <div class="flex items-center">
                                        <input id="approve" name="action" type="radio" value="approve" 
                                               x-model="decision"
                                               class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300">
                                        <label for="approve" class="ml-2 block text-sm font-medium text-green-700">
                                            <svg class="inline w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            Aprobar Solicitud
                                        </label>
                                    </div>
                                    <div class="flex items-center">
                                        <input id="reject" name="action" type="radio" value="reject" 
                                               x-model="decision"
                                               class="h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300">
                                        <label for="reject" class="ml-2 block text-sm font-medium text-red-700">
                                            <svg class="inline w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            Rechazar Solicitud
                                        </label>
                                    </div>
                                </div>
                            </fieldset>

                            <!-- Comentarios -->
                            <div>
                                <label for="comments" class="block text-sm font-medium text-gray-700">
                                    Comentarios
                                    <span x-show="decision === 'reject'" class="text-red-500">*</span>
                                </label>
                                <textarea id="comments" 
                                          name="comments" 
                                          rows="4"
                                          x-model="comments"
                                          :required="decision === 'reject'"
                                          class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                          :placeholder="decision === 'reject' ? 'Explique el motivo del rechazo (obligatorio)...' : 'Comentarios adicionales (opcional)...'"></textarea>
                                <p class="mt-2 text-sm text-gray-500">
                                    <span x-text="comments.length"></span>/1000 caracteres
                                </p>
                            </div>

                            <!-- Información de firma digital (simulada) -->
                            @if(config('app.digital_signature_enabled', false))
                            <div class="bg-gray-50 border border-gray-200 rounded-md p-4">
                                <div class="flex items-center">
                                    <svg class="h-5 w-5 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.586-4H4a2 2 0 00-2 2v6a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5.586z"></path>
                                    </svg>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">Firma Digital</p>
                                        <p class="text-sm text-gray-500">Su decisión será firmada digitalmente con su certificado personal.</p>
                                    </div>
                                </div>
                            </div>
                            @endif

                            <!-- Acciones -->
                            <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                                <a href="{{ route('approvals.index') }}" 
                                   class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    Cancelar
                                </a>
                                <button type="submit" 
                                        :disabled="!decision || (decision === 'reject' && !comments.trim())"
                                        :class="(decision && (decision !== 'reject' || comments.trim())) ? 
                                            (decision === 'approve' ? 'bg-green-600 hover:bg-green-700 focus:ring-green-500' : 'bg-red-600 hover:bg-red-700 focus:ring-red-500') : 
                                            'bg-gray-300 cursor-not-allowed'"
                                        class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white focus:outline-none focus:ring-2 focus:ring-offset-2">
                                    <svg x-show="loading" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span x-show="!loading">
                                        <span x-show="decision === 'approve'">Aprobar Solicitud</span>
                                        <span x-show="decision === 'reject'">Rechazar Solicitud</span>
                                        <span x-show="!decision">Seleccione una Decisión</span>
                                    </span>
                                    <span x-show="loading">Procesando...</span>
                                </button>
                            </div>
                        </div>
                    </form>
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
                            <div class="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center">
                                <span class="text-lg font-medium text-white">
                                    {{ substr($permission->user->first_name, 0, 1) }}{{ substr($permission->user->last_name, 0, 1) }}
                                </span>
                            </div>
                        </div>
                        <div class="ml-4">
                            <p class="text-lg font-medium text-gray-900">{{ $permission->user->full_name }}</p>
                            <p class="text-sm text-gray-500">{{ $permission->user->email }}</p>
                        </div>
                    </div>
                    <dl class="mt-6 space-y-3">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">DNI</dt>
                            <dd class="text-sm text-gray-900">{{ $permission->user->dni }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Departamento</dt>
                            <dd class="text-sm text-gray-900">{{ $permission->user->department->name ?? 'Sin departamento' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Cargo/Rol</dt>
                            <dd class="text-sm text-gray-900">{{ $permission->user->role->name }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Información de la solicitud -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Detalles</h3>
                </div>
                <div class="px-6 py-4">
                    <dl class="space-y-3">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Número de Solicitud</dt>
                            <dd class="text-sm text-gray-900 font-mono">{{ $permission->request_number }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Fecha de Envío</dt>
                            <dd class="text-sm text-gray-900">{{ $permission->submitted_at->format('d/m/Y H:i') }}</dd>
                            <dd class="text-xs text-gray-500">{{ $permission->submitted_at->diffForHumans() }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Tiempo Transcurrido</dt>
                            <dd class="text-sm">
                                @php
                                $hoursElapsed = $permission->submitted_at->diffInHours(now());
                                $isOverdue = $hoursElapsed > 72; // 3 días
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $isOverdue ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800' }}">
                                    {{ $hoursElapsed }} horas
                                    @if($isOverdue)
                                    (Vencido)
                                    @endif
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Prioridad</dt>
                            <dd class="text-sm">
                                @php
                                $priority = $permission->metadata['priority'] ?? 2;
                                $priorityClass = match($priority) {
                                    1 => 'bg-red-100 text-red-800',
                                    3 => 'bg-gray-100 text-gray-800',
                                    default => 'bg-blue-100 text-blue-800'
                                };
                                $priorityLabel = match($priority) {
                                    1 => 'Alta',
                                    3 => 'Baja',
                                    default => 'Normal'
                                };
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $priorityClass }}">
                                    {{ $priorityLabel }}
                                </span>
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Historial de aprobaciones previas -->
            @if($permission->approvals->where('status', '!=', 'pending')->count() > 0)
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Aprobaciones Previas</h3>
                </div>
                <div class="px-6 py-4">
                    <ul class="space-y-3">
                        @foreach($permission->approvals->where('status', '!=', 'pending') as $approval)
                        <li class="flex items-start">
                            <div class="flex-shrink-0">
                                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full {{ $approval->status === 'approved' ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600' }}">
                                    @if($approval->status === 'approved')
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    @else
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                    @endif
                                </span>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-900">{{ $approval->approver->full_name }}</p>
                                <p class="text-xs text-gray-500">{{ $approval->level_name }}</p>
                                <p class="text-xs text-gray-500">{{ $approval->approved_at->format('d/m/Y H:i') }}</p>
                                @if($approval->comments)
                                <p class="text-xs text-gray-600 mt-1">{{ $approval->comments }}</p>
                                @endif
                            </div>
                        </li>
                        @endforeach
                    </ul>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<script>
function approvalForm() {
    return {
        decision: '',
        comments: '',
        loading: false,

        async submitDecision() {
            if (!this.decision) return;
            if (this.decision === 'reject' && !this.comments.trim()) return;

            this.loading = true;

            try {
                const formData = new FormData();
                formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
                formData.append('action', this.decision);
                formData.append('comments', this.comments);

                const response = await fetch('{{ route("approvals.process", $permission) }}', {
                    method: 'POST',
                    body: formData
                });

                if (response.ok) {
                    const result = await response.json();
                    window.location.href = result.redirect || '{{ route("approvals.index") }}';
                } else {
                    const errorData = await response.json();
                    console.error('Error:', errorData);
                    alert('Error al procesar la aprobación. Por favor, inténtelo nuevamente.');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error de conexión. Por favor, inténtelo nuevamente.');
            } finally {
                this.loading = false;
            }
        }
    };
}
</script>

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