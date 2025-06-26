<?php

namespace App\Http\Controllers;

use App\Models\PermissionRequest;
use App\Models\Approval;
use App\Services\ApprovalService;
use App\Http\Requests\ProcessApprovalRequest;
use Illuminate\Http\Request;

class ApprovalController extends Controller
{
    protected ApprovalService $approvalService;

    public function __construct(ApprovalService $approvalService)
    {
        $this->approvalService = $approvalService;
    }

    /**
     * Display a listing of pending approvals.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = PermissionRequest::with(['user.department', 'permissionType', 'approvals.approver']);

        // Filtrar según el rol del usuario
        if ($user->hasRole('jefe_inmediato')) {
            // Mostrar solicitudes de subordinados pendientes de aprobación nivel 1
            $subordinateIds = $user->subordinates()->pluck('id');
            $query->whereIn('user_id', $subordinateIds)
                  ->where('status', PermissionRequest::STATUS_PENDING_SUPERVISOR);
        } elseif ($user->hasRole('jefe_rrhh')) {
            // Mostrar solicitudes pendientes de aprobación nivel 2
            $query->where('status', PermissionRequest::STATUS_PENDING_HR);
        } else {
            // Otros roles no tienen acceso a aprobaciones
            abort(403, 'No tiene permisos para ver aprobaciones.');
        }

        // Aplicar filtros
        if ($request->filled('permission_type')) {
            $query->where('permission_type_id', $request->permission_type);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('submitted_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('submitted_at', '<=', $request->date_to);
        }

        if ($request->filled('priority')) {
            $query->whereJsonContains('metadata->priority', (int)$request->priority);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('request_number', 'like', "%{$search}%")
                  ->orWhere('reason', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($userQuery) use ($search) {
                      $userQuery->where('first_name', 'like', "%{$search}%")
                               ->orWhere('last_name', 'like', "%{$search}%")
                               ->orWhere('dni', 'like', "%{$search}%");
                  });
            });
        }

        // Ordenar por urgencia y fecha de envío
        $query->orderByRaw("
            CASE 
                WHEN JSON_EXTRACT(metadata, '$.is_urgent') = true THEN 1 
                ELSE 2 
            END, 
            submitted_at ASC
        ");

        $requests = $query->paginate(15);

        return view('approvals.index', compact('requests'));
    }

    /**
     * Show the approval form for a specific request.
     */
    public function show(PermissionRequest $permission)
    {
        $user = auth()->user();

        // Verificar que el usuario puede aprobar esta solicitud
        if (!$this->canApproveRequest($user, $permission)) {
            abort(403, 'No tiene permisos para aprobar esta solicitud.');
        }

        $permission->load([
            'user.department',
            'permissionType',
            'approvals.approver',
            'documents',
            'digitalSignatures'
        ]);

        // Determinar el nivel de aprobación actual
        $approvalLevel = $this->getApprovalLevelForUser($user, $permission);

        return view('approvals.show', compact('permission', 'approvalLevel'));
    }

    /**
     * Process the approval (approve or reject).
     */
    public function process(ProcessApprovalRequest $request, PermissionRequest $permission)
    {
        $user = auth()->user();

        // Verificar que el usuario puede aprobar esta solicitud
        if (!$this->canApproveRequest($user, $permission)) {
            abort(403, 'No tiene permisos para aprobar esta solicitud.');
        }

        try {
            $action = $request->validated()['action'];
            $comments = $request->validated()['comments'] ?? '';

            if ($action === 'approve') {
                $result = $this->approvalService->approveRequest($permission, $user, $comments);
                $message = $result['final_approval'] 
                    ? 'Solicitud aprobada exitosamente.' 
                    : 'Solicitud aprobada y enviada al siguiente nivel.';
            } else {
                $this->approvalService->rejectRequest($permission, $user, $comments);
                $message = 'Solicitud rechazada exitosamente.';
            }

            return redirect()
                ->route('approvals.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Error al procesar la aprobación: ' . $e->getMessage());
        }
    }

    /**
     * Approve multiple requests at once.
     */
    public function bulkApprove(Request $request)
    {
        $request->validate([
            'request_ids' => 'required|array',
            'request_ids.*' => 'exists:permission_requests,id',
            'comments' => 'nullable|string|max:500',
        ]);

        $user = auth()->user();
        $comments = $request->comments ?? 'Aprobación masiva';
        $processedCount = 0;
        $errors = [];

        foreach ($request->request_ids as $requestId) {
            try {
                $permission = PermissionRequest::findOrFail($requestId);
                
                if ($this->canApproveRequest($user, $permission)) {
                    $this->approvalService->approveRequest($permission, $user, $comments);
                    $processedCount++;
                } else {
                    $errors[] = "No tiene permisos para aprobar la solicitud #{$permission->request_number}";
                }
            } catch (\Exception $e) {
                $errors[] = "Error al procesar solicitud #{$requestId}: " . $e->getMessage();
            }
        }

        $message = "Se procesaron {$processedCount} solicitudes exitosamente.";
        if (!empty($errors)) {
            $message .= " Errores: " . implode(', ', array_slice($errors, 0, 3));
            if (count($errors) > 3) {
                $message .= " y " . (count($errors) - 3) . " más.";
            }
        }

        return back()->with($processedCount > 0 ? 'success' : 'warning', $message);
    }

    /**
     * Show approval history for a request.
     */
    public function history(PermissionRequest $permission)
    {
        $this->authorize('view', $permission);

        $approvals = $permission->approvals()
            ->with('approver')
            ->orderBy('approval_level')
            ->orderBy('created_at')
            ->get();

        return view('approvals.history', compact('permission', 'approvals'));
    }

    /**
     * Get pending approvals count for AJAX.
     */
    public function getPendingCount()
    {
        $user = auth()->user();
        $count = 0;

        if ($user->hasRole('jefe_inmediato')) {
            $subordinateIds = $user->subordinates()->pluck('id');
            $count = PermissionRequest::whereIn('user_id', $subordinateIds)
                ->where('status', PermissionRequest::STATUS_PENDING_SUPERVISOR)
                ->count();
        } elseif ($user->hasRole('jefe_rrhh')) {
            $count = PermissionRequest::where('status', PermissionRequest::STATUS_PENDING_HR)
                ->count();
        }

        return response()->json(['count' => $count]);
    }

    /**
     * Export approvals to Excel.
     */
    public function export(Request $request)
    {
        // Implementar exportación usando Maatwebsite\Excel
        // Por ahora, retorna un CSV básico
        
        $user = auth()->user();
        $query = PermissionRequest::with(['user', 'permissionType', 'approvals.approver']);

        if ($user->hasRole('jefe_inmediato')) {
            $subordinateIds = $user->subordinates()->pluck('id');
            $query->whereIn('user_id', $subordinateIds);
        }

        $requests = $query->get();

        $filename = 'aprobaciones_' . date('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($requests) {
            $file = fopen('php://output', 'w');
            
            // Headers
            fputcsv($file, [
                'Número de Solicitud',
                'Empleado',
                'Tipo de Permiso',
                'Fecha Inicio',
                'Fecha Fin',
                'Horas',
                'Estado',
                'Fecha Envío',
                'Aprobador',
                'Fecha Aprobación'
            ]);

            foreach ($requests as $request) {
                $lastApproval = $request->approvals->last();
                
                fputcsv($file, [
                    $request->request_number,
                    $request->user->full_name,
                    $request->permissionType->name,
                    $request->start_datetime->format('d/m/Y H:i'),
                    $request->end_datetime->format('d/m/Y H:i'),
                    $request->requested_hours,
                    $request->status,
                    $request->submitted_at?->format('d/m/Y H:i'),
                    $lastApproval?->approver->full_name,
                    $lastApproval?->approved_at?->format('d/m/Y H:i'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Check if user can approve the given request.
     */
    private function canApproveRequest($user, PermissionRequest $permission): bool
    {
        if ($user->hasRole('jefe_inmediato')) {
            return $permission->status === PermissionRequest::STATUS_PENDING_SUPERVISOR
                && $user->subordinates()->where('id', $permission->user_id)->exists();
        }

        if ($user->hasRole('jefe_rrhh')) {
            return $permission->status === PermissionRequest::STATUS_PENDING_HR;
        }

        return false;
    }

    /**
     * Get the approval level for the current user.
     */
    private function getApprovalLevelForUser($user, PermissionRequest $permission): int
    {
        if ($user->hasRole('jefe_inmediato') && 
            $permission->status === PermissionRequest::STATUS_PENDING_SUPERVISOR) {
            return Approval::LEVEL_SUPERVISOR;
        }

        if ($user->hasRole('jefe_rrhh') && 
            $permission->status === PermissionRequest::STATUS_PENDING_HR) {
            return Approval::LEVEL_HR;
        }

        return 0;
    }
}