<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Display a listing of user notifications.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        
        $query = Notification::forUser($user->id)->with('user');

        // Filtros
        if ($request->filled('type')) {
            $query->byType($request->type);
        }

        if ($request->filled('category')) {
            $query->byCategory($request->category);
        }

        if ($request->filled('is_read')) {
            if ($request->is_read === '1') {
                $query->read();
            } else {
                $query->unread();
            }
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $notifications = $query->orderBy('created_at', 'desc')->paginate(20);

        // Estadísticas
        $stats = [
            'total' => Notification::forUser($user->id)->count(),
            'unread' => Notification::forUser($user->id)->unread()->count(),
            'today' => Notification::forUser($user->id)->whereDate('created_at', today())->count(),
        ];

        return view('notifications.index', compact('notifications', 'stats'));
    }

    /**
     * Mark notification as read.
     */
    public function markAsRead(Notification $notification)
    {
        // Verificar que la notificación pertenece al usuario
        if ($notification->user_id !== auth()->id()) {
            abort(403, 'No autorizado para acceder a esta notificación.');
        }

        $notification->markAsRead();

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Notificación marcada como leída.'
            ]);
        }

        return back()->with('success', 'Notificación marcada como leída.');
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead()
    {
        $user = auth()->user();
        $count = $this->notificationService->markAllAsRead($user);

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Se marcaron {$count} notificaciones como leídas.",
                'count' => $count
            ]);
        }

        return back()->with('success', "Se marcaron {$count} notificaciones como leídas.");
    }

    /**
     * Delete a notification.
     */
    public function destroy(Notification $notification)
    {
        // Verificar que la notificación pertenece al usuario
        if ($notification->user_id !== auth()->id()) {
            abort(403, 'No autorizado para eliminar esta notificación.');
        }

        $notification->delete();

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Notificación eliminada exitosamente.'
            ]);
        }

        return back()->with('success', 'Notificación eliminada exitosamente.');
    }

    /**
     * Get unread notifications count for AJAX.
     */
    public function getUnreadCount(): JsonResponse
    {
        $user = auth()->user();
        $count = Notification::forUser($user->id)->unread()->count();

        return response()->json(['count' => $count]);
    }

    /**
     * Get recent unread notifications for AJAX.
     */
    public function getUnreadNotifications(Request $request): JsonResponse
    {
        $user = auth()->user();
        $limit = $request->get('limit', 10);

        $notifications = $this->notificationService->getUnreadNotifications($user, $limit);

        return response()->json($notifications);
    }

    /**
     * Send test notification (for admins).
     */
    public function sendTest(Request $request)
    {
        $this->authorize('manage_system_config');

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:1000',
            'type' => 'required|in:system,email,sms',
        ]);

        $user = \App\Models\User::findOrFail($request->user_id);

        Notification::create([
            'user_id' => $user->id,
            'type' => $request->type,
            'title' => $request->title,
            'message' => $request->message,
            'data' => [
                'category' => 'system',
                'test' => true,
                'sent_by' => auth()->user()->full_name,
            ],
        ]);

        return back()->with('success', 'Notificación de prueba enviada exitosamente.');
    }

    /**
     * Register device for push notifications.
     */
    public function registerDevice(Request $request): JsonResponse
    {
        $request->validate([
            'device_token' => 'required|string',
            'device_type' => 'required|in:ios,android,web',
            'device_name' => 'nullable|string|max:100',
        ]);

        $user = auth()->user();

        // Aquí se guardaría el token del dispositivo para notificaciones push
        // Por ahora, solo simulamos la respuesta exitosa
        
        return response()->json([
            'success' => true,
            'message' => 'Dispositivo registrado para notificaciones push.',
            'device_id' => uniqid('device_')
        ]);
    }

    /**
     * Send push notification (for testing).
     */
    public function sendPushNotification(Request $request): JsonResponse
    {
        $this->authorize('manage_system_config');

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'title' => 'required|string|max:100',
            'body' => 'required|string|max:200',
            'data' => 'nullable|array',
        ]);

        // Aquí se implementaría el envío real de la notificación push
        // Usando Firebase Cloud Messaging, Apple Push Notification Service, etc.

        return response()->json([
            'success' => true,
            'message' => 'Notificación push enviada exitosamente.'
        ]);
    }

    /**
     * Get notification preferences.
     */
    public function getPreferences(): JsonResponse
    {
        $user = auth()->user();
        
        // Simulamos las preferencias del usuario
        $preferences = [
            'email_enabled' => true,
            'sms_enabled' => false,
            'push_enabled' => true,
            'categories' => [
                'permission_request' => true,
                'approval' => true,
                'rejection' => true,
                'reminder' => true,
                'system' => false,
            ],
        ];

        return response()->json($preferences);
    }

    /**
     * Update notification preferences.
     */
    public function updatePreferences(Request $request): JsonResponse
    {
        $request->validate([
            'email_enabled' => 'boolean',
            'sms_enabled' => 'boolean',
            'push_enabled' => 'boolean',
            'categories' => 'array',
            'categories.*' => 'boolean',
        ]);

        $user = auth()->user();
        
        // Aquí se guardarían las preferencias en la base de datos
        // Por ahora, solo simulamos la respuesta exitosa
        
        return response()->json([
            'success' => true,
            'message' => 'Preferencias de notificaciones actualizadas exitosamente.'
        ]);
    }

    /**
     * Get notification statistics.
     */
    public function getStatistics(Request $request): JsonResponse
    {
        $user = auth()->user();
        $days = $request->get('days', 30);
        $startDate = now()->subDays($days);

        $stats = [
            'total' => Notification::forUser($user->id)->count(),
            'unread' => Notification::forUser($user->id)->unread()->count(),
            'recent' => Notification::forUser($user->id)
                ->where('created_at', '>=', $startDate)
                ->count(),
            'by_type' => Notification::forUser($user->id)
                ->where('created_at', '>=', $startDate)
                ->selectRaw('type, count(*) as count')
                ->groupBy('type')
                ->pluck('count', 'type'),
            'by_category' => Notification::forUser($user->id)
                ->where('created_at', '>=', $startDate)
                ->whereNotNull('data')
                ->get()
                ->groupBy(function ($notification) {
                    return $notification->data['category'] ?? 'other';
                })
                ->map->count(),
            'daily_counts' => $this->getDailyCounts($user->id, $days),
        ];

        return response()->json($stats);
    }

    /**
     * Get daily notification counts.
     */
    private function getDailyCounts(int $userId, int $days): array
    {
        $data = [];
        
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $count = Notification::forUser($userId)
                ->whereDate('created_at', $date)
                ->count();
            
            $data[] = [
                'date' => $date->format('Y-m-d'),
                'day' => $date->format('d/m'),
                'count' => $count,
            ];
        }

        return $data;
    }

    /**
     * Export notifications to CSV.
     */
    public function export(Request $request)
    {
        $user = auth()->user();
        
        $query = Notification::forUser($user->id);
        
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $notifications = $query->orderBy('created_at', 'desc')->get();

        $filename = 'notificaciones_' . $user->dni . '_' . date('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($notifications) {
            $file = fopen('php://output', 'w');
            
            // Headers
            fputcsv($file, [
                'Fecha',
                'Tipo',
                'Título',
                'Mensaje',
                'Categoría',
                'Leída',
                'Fecha de Lectura'
            ]);

            foreach ($notifications as $notification) {
                fputcsv($file, [
                    $notification->created_at->format('d/m/Y H:i'),
                    $notification->type,
                    $notification->title,
                    $notification->message,
                    $notification->data['category'] ?? '',
                    $notification->is_read ? 'Sí' : 'No',
                    $notification->read_at?->format('d/m/Y H:i'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Cleanup old notifications.
     */
    public function cleanup(Request $request): JsonResponse
    {
        $this->authorize('manage_system_config');

        $request->validate([
            'days' => 'nullable|integer|min:1|max:365',
        ]);

        $days = $request->get('days', 90);
        $deletedCount = $this->notificationService->cleanupOldNotifications($days);

        return response()->json([
            'success' => true,
            'message' => "Se eliminaron {$deletedCount} notificaciones antiguas.",
            'deleted_count' => $deletedCount
        ]);
    }

    /**
     * Process daily notifications (for cron job).
     */
    public function processDailyNotifications(): JsonResponse
    {
        $this->authorize('manage_system_config');

        $results = $this->notificationService->processDailyNotifications();

        return response()->json([
            'success' => true,
            'message' => 'Notificaciones diarias procesadas exitosamente.',
            'results' => $results
        ]);
    }
}