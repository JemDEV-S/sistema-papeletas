@extends('layouts.app')

@section('title', 'Nueva Solicitud de Permiso')

@php
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => route('dashboard')],
    ['title' => 'Solicitudes', 'url' => route('permissions.index')],
    ['title' => 'Nueva Solicitud', 'url' => ''],
];
@endphp

@section('content')
<div x-data="permissionRequestForm()" class="space-y-6">
    <!-- Header -->
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Nueva Solicitud de Permiso</h1>
        <p class="mt-2 text-sm text-gray-700">Complete el formulario para solicitar un permiso laboral</p>
    </div>

    <!-- Formulario HTML tradicional -->
    <form action="{{ route('permissions.store') }}" method="POST" class="space-y-6">
        @csrf

        <!-- Información del solicitante -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Información del Solicitante</h3>
            </div>
            <div class="px-6 py-4">
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nombre Completo</label>
                        <p class="mt-1 text-sm text-gray-900">{{ auth()->user()->full_name }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">DNI</label>
                        <p class="mt-1 text-sm text-gray-900">{{ auth()->user()->dni }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Departamento</label>
                        <p class="mt-1 text-sm text-gray-900">{{ auth()->user()->department->name ?? 'Sin departamento' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Supervisor Inmediato</label>
                        <p class="mt-1 text-sm text-gray-900">
                            {{ auth()->user()->immediateSupervisor->full_name ?? 'No asignado' }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detalles del permiso -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Detalles del Permiso</h3>
            </div>
            <div class="px-6 py-4 space-y-6">
                <!-- Tipo de permiso -->
                <div>
                    <label for="permission_type_id" class="block text-sm font-medium text-gray-700">
                        Tipo de Permiso <span class="text-red-500">*</span>
                    </label>
                    <select id="permission_type_id" 
                            name="permission_type_id" 
                            x-model="selectedPermissionType"
                            @change="loadPermissionTypeDetails()"
                            class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md"
                            required>
                        <option value="">Seleccione un tipo de permiso</option>
                        @foreach($permissionTypes as $type)
                        <option value="{{ $type->id }}" {{ old('permission_type_id') == $type->id ? 'selected' : '' }}>
                            {{ $type->name }}
                        </option>
                        @endforeach
                    </select>
                    @error('permission_type_id')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Información del tipo de permiso -->
                <div x-show="permissionTypeInfo" x-transition class="bg-blue-50 border border-blue-200 rounded-md p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-blue-800">Información del Tipo de Permiso</h3>
                            <div class="mt-2 text-sm text-blue-700">
                                <p x-text="permissionTypeInfo?.description"></p>
                                <div class="mt-2 space-y-1">
                                    <div x-show="permissionTypeInfo?.max_hours_per_day">
                                        <strong>Máximo por día:</strong> <span x-text="permissionTypeInfo?.max_hours_per_day"></span> horas
                                    </div>
                                    <div x-show="permissionTypeInfo?.max_hours_per_month">
                                        <strong>Máximo por mes:</strong> <span x-text="permissionTypeInfo?.max_hours_per_month"></span> horas
                                    </div>
                                    <div x-show="permissionTypeInfo?.requires_document">
                                        <strong>Requiere documentos:</strong> Sí
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Saldo disponible -->
                <div x-show="permissionBalance" x-transition class="bg-green-50 border border-green-200 rounded-md p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-green-800">Saldo Disponible</h3>
                            <div class="mt-2 text-sm text-green-700">
                                <span x-text="permissionBalance?.remaining_hours"></span> horas disponibles de 
                                <span x-text="permissionBalance?.available_hours"></span> horas totales
                                <div class="mt-1 w-full bg-green-200 rounded-full h-2">
                                    <div class="bg-green-600 h-2 rounded-full" 
                                         :style="`width: ${(permissionBalance?.remaining_hours / permissionBalance?.available_hours) * 100}%`"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Fechas y horas -->
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <label for="start_datetime" class="block text-sm font-medium text-gray-700">
                            Fecha y Hora de Inicio <span class="text-red-500">*</span>
                        </label>
                        <input type="datetime-local" 
                               id="start_datetime" 
                               name="start_datetime"
                               value="{{ old('start_datetime') }}"
                               x-model="startDateTime"
                               @change="calculateHoursWithoutAPI()"
                               :min="minDateTime"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                               required>
                        @error('start_datetime')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="end_datetime" class="block text-sm font-medium text-gray-700">
                            Fecha y Hora de Fin <span class="text-red-500">*</span>
                        </label>
                        <input type="datetime-local" 
                               id="end_datetime" 
                               name="end_datetime"
                               value="{{ old('end_datetime') }}"
                               x-model="endDateTime"
                               @change="calculateHoursWithoutAPI()"
                               :min="startDateTime"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                               required>
                        @error('end_datetime')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Horas calculadas -->
                <div x-show="calculatedHours > 0" x-transition class="bg-gray-50 border border-gray-200 rounded-md p-4">
                    <div class="flex items-center">
                        <svg class="h-5 w-5 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="text-sm font-medium text-gray-700">
                            Duración calculada: <span class="text-blue-600 font-bold" x-text="calculatedHours"></span> horas
                        </span>
                    </div>
                </div>

                <!-- Motivo -->
                <div>
                    <label for="reason" class="block text-sm font-medium text-gray-700">
                        Motivo del Permiso <span class="text-red-500">*</span>
                    </label>
                    <textarea id="reason" 
                              name="reason" 
                              rows="3"
                              x-model="reason"
                              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                              placeholder="Describa el motivo de su solicitud..."
                              required>{{ old('reason') }}</textarea>
                    <p class="mt-2 text-sm text-gray-500">
                        <span x-text="reason.length"></span>/500 caracteres
                    </p>
                    @error('reason')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Opciones adicionales -->
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <label for="priority" class="block text-sm font-medium text-gray-700">Prioridad</label>
                        <select id="priority" 
                                name="priority"
                                x-model="priority"
                                class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                            <option value="3" {{ old('priority', '2') == '3' ? 'selected' : '' }}>Baja</option>
                            <option value="2" {{ old('priority', '2') == '2' ? 'selected' : '' }}>Normal</option>
                            <option value="1" {{ old('priority', '2') == '1' ? 'selected' : '' }}>Alta</option>
                        </select>
                    </div>

                    <div class="flex items-center">
                        <input id="is_urgent" 
                               name="is_urgent"
                               type="checkbox"
                               value="1"
                               {{ old('is_urgent') ? 'checked' : '' }}
                               x-model="isUrgent"
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="is_urgent" class="ml-2 block text-sm font-medium text-gray-700">
                            Marcar como urgente
                        </label>
                    </div>
                </div>

                <!-- Información adicional -->
                <div>
                    <label for="additional_info" class="block text-sm font-medium text-gray-700">
                        Información Adicional
                    </label>
                    <textarea id="additional_info" 
                              name="additional_info"
                              rows="2"
                              x-model="additionalInfo"
                              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                              placeholder="Información adicional relevante (opcional)...">{{ old('additional_info') }}</textarea>
                    @error('additional_info')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Documentos requeridos -->
        <div x-show="permissionTypeInfo?.requires_document" x-transition class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Documentos Requeridos</h3>
                <p class="mt-1 text-sm text-gray-500">
                    Este tipo de permiso requiere documentos de respaldo
                </p>
            </div>
            <div class="px-6 py-4">
                <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.268 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-yellow-800">Documentos Necesarios</h3>
                            <div class="mt-2 text-sm text-yellow-700">
                                <p>Puede subir los documentos después de crear la solicitud, pero debe enviarlos antes de la aprobación final.</p>
                                <div class="mt-2">
                                    <template x-for="doc in (permissionTypeInfo?.required_documents || [])" :key="doc">
                                        <div class="flex items-center mt-1">
                                            <svg class="h-4 w-4 text-yellow-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            </svg>
                                            <span x-text="getDocumentName(doc)"></span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Acciones -->
        <div class="flex justify-end space-x-3">
            <a href="{{ route('permissions.index') }}" 
               class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                Cancelar
            </a>
            <button type="submit" 
                    :disabled="!isFormValid()"
                    :class="isFormValid() ? 'bg-blue-600 hover:bg-blue-700 focus:ring-blue-500' : 'bg-gray-300 cursor-not-allowed'"
                    class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white focus:outline-none focus:ring-2 focus:ring-offset-2">
                Crear Solicitud
            </button>
        </div>
    </form>
</div>

<script>
function permissionRequestForm() {
    return {
        selectedPermissionType: '{{ old('permission_type_id', '') }}',
        permissionTypeInfo: null,
        permissionBalance: null,
        startDateTime: '{{ old('start_datetime', '') }}',
        endDateTime: '{{ old('end_datetime', '') }}',
        calculatedHours: 0,
        reason: '{{ old('reason', '') }}',
        priority: '{{ old('priority', '2') }}',
        isUrgent: {{ old('is_urgent') ? 'true' : 'false' }},
        additionalInfo: '{{ old('additional_info', '') }}',
        
        init() {
            // Si hay un tipo de permiso seleccionado (por old input), cargar sus detalles
            if (this.selectedPermissionType) {
                this.loadPermissionTypeDetails();
            }
            
            // Calcular horas si hay fechas pre-seleccionadas
            if (this.startDateTime && this.endDateTime) {
                this.calculateHoursWithoutAPI();
            }
        },
        
        // Función helper para obtener fecha/hora local en formato correcto
        getLocalDateTimeString(addHours = 0) {
            const now = new Date();
            now.setHours(now.getHours() + addHours);
            
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            
            return `${year}-${month}-${day}T${hours}:${minutes}`;
        },

        // Inicializar minDateTime correctamente
        get minDateTime() {
            return this.getLocalDateTimeString(2); // 2 horas desde ahora
        },

        async loadPermissionTypeDetails() {
            if (!this.selectedPermissionType) {
                this.permissionTypeInfo = null;
                this.permissionBalance = null;
                return;
            }

            try {
                const response = await fetch(`/permissions/api/permission-types/${this.selectedPermissionType}`);
                const data = await response.json();
                
                this.permissionTypeInfo = data.permission_type;
                this.permissionBalance = data.balance;
            } catch (error) {
                console.error('Error loading permission type details:', error);
                // En caso de error, no impedir el envío del formulario
            }
        },

        // Función simplificada para calcular horas sin API
        calculateHoursWithoutAPI() {
            if (!this.startDateTime || !this.endDateTime) {
                this.calculatedHours = 0;
                return;
            }

            const start = new Date(this.startDateTime);
            const end = new Date(this.endDateTime);
            
            if (end <= start) {
                this.calculatedHours = 0;
                return;
            }

            // Calcular diferencia en milisegundos y convertir a horas
            const diffMs = end - start;
            const diffHours = Math.round((diffMs / (1000 * 60 * 60)) * 100) / 100; // Redondear a 2 decimales
            
            this.calculatedHours = diffHours;
        },

        isFormValid() {
            return this.selectedPermissionType && 
                   this.startDateTime && 
                   this.endDateTime && 
                   this.reason.length >= 10 && 
                   this.calculatedHours > 0;
        },

        getDocumentName(docType) {
            const names = {
                'certificado_medico': 'Certificado Médico',
                'certificado_control_medico': 'Certificado de Control Médico',
                'copia_citacion': 'Copia de Citación',
                'acreditacion_edil': 'Acreditación Edil',
                'resolucion_nombramiento': 'Resolución de Nombramiento',
                'horario_visado': 'Horario Visado',
                'horario_recuperacion': 'Horario de Recuperación',
                'partida_nacimiento': 'Partida de Nacimiento',
                'declaracion_jurada_supervivencia': 'Declaración Jurada de Supervivencia'
            };
            return names[docType] || docType;
        }
    };
}
</script>
@endsection