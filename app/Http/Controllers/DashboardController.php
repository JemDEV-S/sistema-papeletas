<?php

namespace App\Http\Controllers;

use App\Models\PermissionRequest;
use App\Models\PermissionBalance;
use App\Models\Notification;
use App\Models\User;
use App\Models\PermissionType;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Display the dashboard.
     */
    public function index()
    {
        $user = auth()->user();
        
        $data = match($user->role->name) {
            'administrador' => $this->getAdminDashboardData(),
            'jefe_rrhh' => $this->getHRDashboardData(),
            'jefe_inmediato' => $this->getSupervisorDashboardData(),
            'empleado' => $this->getEmployeeDashboardData(),
            default => $this->getDefaultDashboardData()
        };

        return view('dashboard.index', $data);
    }

    /**
     * Datos del dashboard para administrador
     */
    private function getAdminDashboardData(): array
    {
        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();
        
        return [
            'stats' => [
                'total_users' => User::active()->count(),
                'pending_requests' => PermissionRequest::pending()->count(),
                'approved_today' => PermissionRequest::approved()
                    ->whereDate('updated_at', $today)
                    ->count(),
                'requests_this_month' => PermissionRequest::whereDate('created_at', '>=', $thisMonth)->count(),
            ],
            'recent_requests' => PermissionRequest::with(['user', 'permissionType'])
                ->latest()
                ->limit(10)
                ->get(),
            'permission_usage' => $this->getPermissionUsageStats(),
            'department_stats' => $this->getDepartmentStats(),
            'chart_data' => $this->getRequestsChartData(),
        ];
    }

    /**
     * Datos del dashboard para jefe de RRHH
     */
    private function getHRDashboardData(): array
    {
        return [
            'stats' => [
                'pending_hr_approval' => PermissionRequest::where('status', PermissionRequest::STATUS_PENDING_HR)->count(),
                'approved_today' => PermissionRequest::approved()
                    ->whereDate('updated_at', Carbon::today())
                    ->count(),
                'requests_this_month' => PermissionRequest::whereMonth('created_at', Carbon::now()->month)->count(),
                'total_hours_approved' => PermissionRequest::approved()
                    ->whereMonth('created_at', Carbon::now()->month)
                    ->sum('requested_hours'),
            ],
            'pending_approvals' => PermissionRequest::with(['user', 'permissionType'])
                ->where('status', PermissionRequest::STATUS_PENDING_HR)
                ->orderBy('submitted_at')
                ->limit(10)
                ->get(),
            'recent_approvals' => PermissionRequest::with(['user', 'permissionType', 'approvals.approver'])
                ->approved()
                ->latest('updated_at')
                ->limit(5)
                ->get(),
            'balance_alerts' => $this->getBalanceAlerts(),
        ];
    }

    /**
     * Datos del dashboard para jefe inmediato
     */
    private function getSupervisorDashboardData(): array
    {
        $user = auth()->user();
        $subordinateIds = $user->subordinates()->pluck('id');

        return [
            'stats' => [
                'subordinates_count' => $subordinateIds->count(),
                'pending_supervisor_approval' => PermissionRequest::whereIn('user_id', $subordinateIds)
                    ->where('status', PermissionRequest::STATUS_PENDING_SUPERVISOR)
                    ->count(),
                'approved_this_month' => PermissionRequest::whereIn('user_id', $subordinateIds)
                    ->approved()
                    ->whereMonth('created_at', Carbon::now()->month)
                    ->count(),
                'team_hours_used' => PermissionRequest::whereIn('user_id', $subordinateIds)
                    ->approved()
                    ->whereMonth('created_at', Carbon::now()->month)
                    ->sum('requested_hours'),
            ],
            'pending_approvals' => PermissionRequest::with(['user', 'permissionType'])
                ->whereIn('user_id', $subordinateIds)
                ->where('status', PermissionRequest::STATUS_PENDING_SUPERVISOR)
                ->orderBy('submitted_at')
                ->limit(10)
                ->get(),
            'team_requests' => PermissionRequest::with(['user', 'permissionType'])
                ->whereIn('user_id', $subordinateIds)
                ->latest()
                ->limit(8)
                ->get(),
            'subordinates' => $user->subordinates()->with('department')->get(),
        ];
    }

    /**
     * Datos del dashboard para empleado
     */
    private function getEmployeeDashboardData(): array
    {
        $user = auth()->user();
        $currentMonth = Carbon::now();

        return [
            'stats' => [
                'total_requests' => $user->permissionRequests()->count(),
                'pending_requests' => $user->permissionRequests()->pending()->count(),
                'approved_requests' => $user->permissionRequests()->approved()->count(),
                'hours_used_this_month' => $user->permissionRequests()
                    ->approved()
                    ->whereMonth('created_at', $currentMonth->month)
                    ->whereYear('created_at', $currentMonth->year)
                    ->sum('requested_hours'),
            ],
            'recent_requests' => $user->permissionRequests()
                ->with(['permissionType', 'approvals.approver'])
                ->latest()
                ->limit(5)
                ->get(),
            'permission_balances' => PermissionBalance::with('permissionType')
                ->where('user_id', $user->id)
                ->where('year', $currentMonth->year)
                ->where('month', $currentMonth->month)
                ->where('available_hours', '>', 0)
                ->get(),
            'active_permissions' => $user->permissionRequests()
                ->with('permissionType')
                ->where('status', PermissionRequest::STATUS_IN_EXECUTION)
                ->get(),
            'upcoming_permissions' => $user->permissionRequests()
                ->with('permissionType')
                ->approved()
                ->where('start_datetime', '>', Carbon::now())
                ->where('start_datetime', '<=', Carbon::now()->addDays(7))
                ->orderBy('start_datetime')
                ->get(),
        ];
    }

    /**
     * Datos por defecto del dashboard
     */
    private function getDefaultDashboardData(): array
    {
        return [
            'stats' => [
                'message' => 'Bienvenido al Sistema de Papeletas Digitales',
            ],
            'notifications' => Notification::forUser(auth()->id())
                ->unread()
                ->recent(7)
                ->limit(5)
                ->get(),
        ];
    }

    /**
     * Estadísticas de uso de permisos
     */
    private function getPermissionUsageStats(): array
    {
        $currentMonth = Carbon::now();
        
        return PermissionType::withCount([
            'permissionRequests as total_requests',
            'permissionRequests as approved_requests' => function ($query) {
                $query->approved();
            },
            'permissionRequests as current_month_requests' => function ($query) use ($currentMonth) {
                $query->whereMonth('created_at', $currentMonth->month)
                      ->whereYear('created_at', $currentMonth->year);
            }
        ])->get()->map(function ($type) {
            return [
                'name' => $type->name,
                'total' => $type->total_requests,
                'approved' => $type->approved_requests,
                'current_month' => $type->current_month_requests,
                'approval_rate' => $type->total_requests > 0 ? 
                    round(($type->approved_requests / $type->total_requests) * 100, 1) : 0,
            ];
        })->toArray();
    }

    /**
     * Estadísticas por departamento
     */
    private function getDepartmentStats(): array
    {
        return DB::table('departments')
            ->leftJoin('users', 'departments.id', '=', 'users.department_id')
            ->leftJoin('permission_requests', 'users.id', '=', 'permission_requests.user_id')
            ->select(
                'departments.name',
                DB::raw('COUNT(DISTINCT users.id) as total_users'),
                DB::raw('COUNT(permission_requests.id) as total_requests'),
                DB::raw('COUNT(CASE WHEN permission_requests.status = "approved" THEN 1 END) as approved_requests'),
                DB::raw('SUM(CASE WHEN permission_requests.status = "approved" THEN permission_requests.requested_hours ELSE 0 END) as total_hours')
            )
            ->where('departments.is_active', true)
            ->groupBy('departments.id', 'departments.name')
            ->get()
            ->toArray();
    }

    /**
     * Datos para gráfico de solicitudes
     */
    private function getRequestsChartData(): array
    {
        $last30Days = collect();
        
        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $last30Days->push([
                'date' => $date->format('Y-m-d'),
                'day' => $date->format('d/m'),
                'requests' => PermissionRequest::whereDate('created_at', $date)->count(),
                'approved' => PermissionRequest::approved()->whereDate('updated_at', $date)->count(),
            ]);
        }
        
        return $last30Days->toArray();
    }

    /**
     * Alertas de saldos bajos
     */
    private function getBalanceAlerts(): array
    {
        return PermissionBalance::with(['user', 'permissionType'])
            ->currentMonth()
            ->where('remaining_hours', '>', 0)
            ->whereRaw('(used_hours / available_hours) >= 0.8')
            ->orderByDesc(DB::raw('(used_hours / available_hours)'))
            ->limit(10)
            ->get()
            ->map(function ($balance) {
                return [
                    'user' => $balance->user->full_name,
                    'permission_type' => $balance->permissionType->name,
                    'usage_percentage' => $balance->usage_percentage,
                    'remaining_hours' => $balance->remaining_hours,
                    'available_hours' => $balance->available_hours,
                ];
            })
            ->toArray();
    }

    /**
     * API endpoint para datos del dashboard
     */
    public function apiData(Request $request)
    {
        $type = $request->get('type', 'stats');
        
        return response()->json(match($type) {
            'chart' => $this->getRequestsChartData(),
            'usage' => $this->getPermissionUsageStats(),
            'departments' => $this->getDepartmentStats(),
            'alerts' => $this->getBalanceAlerts(),
            default => ['error' => 'Tipo de datos no válido']
        });
    }
}