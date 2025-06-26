<?php

namespace App\Http\Controllers;

use App\Models\PermissionRequest;
use App\Models\PermissionType;
use App\Models\PermissionBalance;
use App\Models\Document;
use App\Services\PermissionRequestService;
use App\Http\Requests\StorePermissionRequestRequest;
use App\Http\Requests\UpdatePermissionRequestRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class PermissionRequestController extends Controller
{
    use AuthorizesRequests;
    protected PermissionRequestService $permissionService;

    public function __construct(PermissionRequestService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = PermissionRequest::with(['user', 'permissionType', 'approvals.approver']);

        // Filtrar según el rol del usuario
        if ($user->hasRole('empleado')) {
            $query->where('user_id', $user->id);
        } elseif ($user->hasRole('jefe_inmediato')) {
            $subordinateIds = $user->subordinates()->pluck('id');
            $query->where(function ($q) use ($user, $subordinateIds) {
                $q->where('user_id', $user->id)
                  ->orWhereIn('user_id', $subordinateIds);
            });
        }

        // Aplicar filtros
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('permission_type')) {
            $query->where('permission_type_id', $request->permission_type);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('start_datetime', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('start_datetime', '<=', $request->date_to);
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

        $requests = $query->orderBy('created_at', 'desc')->paginate(15);
        $permissionTypes = PermissionType::active()->get();

        return view('permissions.index', compact('requests', 'permissionTypes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('create', PermissionRequest::class);

        $user = auth()->user();
        $permissionTypes = PermissionType::active()->get();
        
        // Obtener saldos disponibles del usuario para el mes actual
        $currentMonth = Carbon::now();
        $balances = PermissionBalance::with('permissionType')
            ->where('user_id', $user->id)
            ->where('year', $currentMonth->year)
            ->where('month', $currentMonth->month)
            ->get()
            ->keyBy('permission_type_id');

        return view('permissions.create', compact('permissionTypes', 'balances'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePermissionRequestRequest $request)
    {
        try {
            $permissionRequest = $this->permissionService->createRequest(
                auth()->user(),
                $request->validated()
            );

            return redirect()
                ->route('permissions.show', $permissionRequest)
                ->with('success', 'Solicitud de permiso creada exitosamente.');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Error al crear la solicitud: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(PermissionRequest $permission)
    {
        $this->authorize('view', $permission);

        $permission->load([
            'user.department',
            'permissionType',
            'approvals.approver',
            'documents',
            'digitalSignatures',
            'biometricRecords'
        ]);

        return view('permissions.show', compact('permission'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PermissionRequest $permission)
    {
        $this->authorize('update', $permission);

        if (!$permission->status === PermissionRequest::STATUS_DRAFT) {
            return back()->with('error', 'Solo se pueden editar solicitudes en estado borrador.');
        }

        $permissionTypes = PermissionType::active()->get();
        
        $user = $permission->user;
        $currentMonth = Carbon::parse($permission->start_datetime);
        $balances = PermissionBalance::with('permissionType')
            ->where('user_id', $user->id)
            ->where('year', $currentMonth->year)
            ->where('month', $currentMonth->month)
            ->get()
            ->keyBy('permission_type_id');

        return view('permissions.edit', compact('permission', 'permissionTypes', 'balances'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePermissionRequestRequest $request, PermissionRequest $permission)
    {
        try {
            $updatedPermission = $this->permissionService->updateRequest(
                $permission,
                $request->validated()
            );

            return redirect()
                ->route('permissions.show', $updatedPermission)
                ->with('success', 'Solicitud actualizada exitosamente.');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Error al actualizar la solicitud: ' . $e->getMessage());
        }
    }

    /**
     * Submit the request for approval.
     */
    public function submit(PermissionRequest $permission)
    {
        $this->authorize('update', $permission);

        try {
            $this->permissionService->submitRequest($permission);

            return back()->with('success', 'Solicitud enviada para aprobación exitosamente.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al enviar la solicitud: ' . $e->getMessage());
        }
    }

    /**
     * Cancel the request.
     */
    public function cancel(PermissionRequest $permission)
    {
        $this->authorize('delete', $permission);

        try {
            $this->permissionService->cancelRequest($permission);

            return back()->with('success', 'Solicitud cancelada exitosamente.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al cancelar la solicitud: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PermissionRequest $permission)
    {
        $this->authorize('delete', $permission);

        if ($permission->status !== PermissionRequest::STATUS_DRAFT) {
            return back()->with('error', 'Solo se pueden eliminar solicitudes en estado borrador.');
        }

        try {
            $permission->delete();
            return redirect()
                ->route('permissions.index')
                ->with('success', 'Solicitud eliminada exitosamente.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al eliminar la solicitud: ' . $e->getMessage());
        }
    }

    /**
     * Upload document for permission request.
     */
    public function uploadDocument(Request $request, PermissionRequest $permission)
    {
        $this->authorize('update', $permission);

        $request->validate([
            'document' => 'required|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120',
            'document_type' => 'required|string|in:' . implode(',', [
                Document::TYPE_CERTIFICADO_MEDICO,
                Document::TYPE_CERTIFICADO_CONTROL_MEDICO,
                Document::TYPE_COPIA_CITACION,
                Document::TYPE_ACREDITACION_EDIL,
                Document::TYPE_RESOLUCION_NOMBRAMIENTO,
                Document::TYPE_HORARIO_VISADO,
                Document::TYPE_HORARIO_RECUPERACION,
                Document::TYPE_PARTIDA_NACIMIENTO,
                Document::TYPE_DECLARACION_JURADA,
                Document::TYPE_OTROS,
            ]),
        ]);

        try {
            $file = $request->file('document');
            $storedName = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('permission_documents', $storedName, 'private');

            $document = Document::create([
                'permission_request_id' => $permission->id,
                'original_name' => $file->getClientOriginalName(),
                'stored_name' => $storedName,
                'file_path' => $path,
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'document_type' => $request->document_type,
                'file_hash' => Document::generateFileHash($file->getPathname()),
            ]);

            return back()->with('success', 'Documento subido exitosamente.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al subir el documento: ' . $e->getMessage());
        }
    }

    /**
     * Download document.
     */
    public function downloadDocument(Document $document)
    {
        $this->authorize('view', $document->permissionRequest);

        if (!Storage::disk('private')->exists($document->file_path)) {
            abort(404, 'Archivo no encontrado.');
        }

        return Storage::disk('private')->download(
            $document->file_path,
            $document->original_name
        );
    }

    /**
     * Delete document.
     */
    public function deleteDocument(Document $document)
    {
        $this->authorize('update', $document->permissionRequest);

        try {
            if (Storage::disk('private')->exists($document->file_path)) {
                Storage::disk('private')->delete($document->file_path);
            }

            $document->delete();

            return back()->with('success', 'Documento eliminado exitosamente.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al eliminar el documento: ' . $e->getMessage());
        }
    }

    /**
     * Get permission type details for AJAX.
     */
    public function getPermissionTypeDetails(PermissionType $permissionType)
    {
        $user = auth()->user();
        $currentMonth = Carbon::now();
        
        $balance = PermissionBalance::getOrCreateBalance(
            $user->id,
            $permissionType->id,
            $currentMonth->year,
            $currentMonth->month
        );

        return response()->json([
            'permission_type' => $permissionType,
            'balance' => $balance,
            'required_documents' => $permissionType->getRequiredDocuments(),
            'validation_rules' => $permissionType->validation_rules,
        ]);
    }

    /**
     * Calculate hours between dates for AJAX.
     */
    public function calculateHours(Request $request)
    {
        $request->validate([
            'start_datetime' => 'required|date',
            'end_datetime' => 'required|date|after:start_datetime',
        ]);

        $start = Carbon::parse($request->start_datetime);
        $end = Carbon::parse($request->end_datetime);
        
        $hours = $start->diffInHours($end, true);

        return response()->json([
            'hours' => round($hours, 2),
            'start_formatted' => $start->format('d/m/Y H:i'),
            'end_formatted' => $end->format('d/m/Y H:i'),
        ]);
    }

    /**
     * Get user's permission balance for AJAX.
     */
    public function getUserBalance(Request $request)
    {
        $request->validate([
            'permission_type_id' => 'required|exists:permission_types,id',
            'year' => 'sometimes|integer',
            'month' => 'sometimes|integer|between:1,12',
        ]);

        $user = auth()->user();
        $year = $request->get('year', Carbon::now()->year);
        $month = $request->get('month', Carbon::now()->month);

        $balance = PermissionBalance::getOrCreateBalance(
            $user->id,
            $request->permission_type_id,
            $year,
            $month
        );

        return response()->json($balance);
    }
}