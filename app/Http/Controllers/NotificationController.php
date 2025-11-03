<?php


namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\AppUserNotification;
use App\Services\NotificationService;

class NotificationController extends Controller
{
    public function __construct(
        protected NotificationService $notifications
    )
    {
    }

    // GET /api/notifications
    public function index(Request $request)
    {
        $user = $request->user();
        $perPage = (int)$request->query('per_page', 20);

        $rows = AppUserNotification::where('user_id', $user->id)
            ->latest('id')
            ->paginate($perPage);

        // علّمهم مقروء الآن (اختياري)
        AppUserNotification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => Carbon::now()]);

        $rows->getCollection()->transform(function ($n) {
            return [
                'id' => $n->id,
                'title' => $n->title,
                'body' => $n->body,
                'type' => $n->type,
                'order_id' => $n->order_id,
                'data' => $n->data,
                'is_read' => $n->read_at !== null,
                'read_at' => optional($n->read_at)->toIso8601String(),
                'created_at' => $n->created_at->toIso8601String(),
            ];
        });

        return response()->json($rows);
    }

    // GET /api/notifications/unread-count
    public function unreadCount(Request $request)
    {
        $user = $request->user();
        $count = $this->notifications->unreadCountForUser($user->id);

        return response()->json([
            'unread_count' => $count,
        ]);
    }

    // POST /api/notifications/{id}/mark-read
    public function markRead(Request $request, int $id)
    {
        $user = $request->user();
        $this->notifications->markOneRead($user->id, $id);

        return response()->json(['message' => 'marked as read']);
    }

    // POST /api/notifications/mark-all-read
    public function markAllRead(Request $request)
    {
        $user = $request->user();
        $this->notifications->markAllReadForUser($user->id);

        return response()->json(['message' => 'all marked as read']);
    }
}

//
//namespace App\Http\Controllers;
//
//use Illuminate\Http\Request;
//use Illuminate\Support\Carbon;
//use App\Models\AppUserNotification;
//use App\Services\NotificationService;
//
//class NotificationController extends Controller
//{
//    public function __construct(
//        protected NotificationService $notificationService
//    ) {}
//
//    // GET /api/notifications
//    // يرجع الإشعارات (paginated)
//    // ويعلمها مقروء إذا بدك هذا السلوك هنا
//    public function index(Request $request)
//    {
//        $user     = $request->user();
//        $perPage  = (int) $request->query('per_page', 20);
//
//        $rows = AppUserNotification::where('user_id', $user->id)
//            ->latest('id')
//            ->paginate($perPage);
//
//        // (اختياري): علم الكل كمقروء عند فتح صفحة الإشعارات
//        AppUserNotification::where('user_id', $user->id)
//            ->whereNull('read_at')
//            ->update(['read_at' => Carbon::now()]);
//
//        $rows->getCollection()->transform(function ($n) {
//            return [
//                'id'         => $n->id,
//                'title'      => $n->title,
//                'body'       => $n->body,
//                'type'       => $n->type,
//                'order_id'   => $n->order_id,
//                'data'       => $n->data,
//                'is_read'    => $n->read_at !== null,
//                'read_at'    => optional($n->read_at)->toIso8601String(),
//                'created_at' => $n->created_at->toIso8601String(),
//            ];
//        });
//
//        return response()->json($rows);
//    }
//
//    // POST /api/notifications/{id}/mark-read
//    public function markRead(Request $request, int $id)
//    {
//        $user = $request->user();
//        $this->notificationService->markOneRead($user->id, $id);
//
//        return response()->json(['message' => 'marked as read']);
//    }
//
//    // POST /api/notifications/mark-all-read
//    public function markAllRead(Request $request)
//    {
//        $user = $request->user();
//        $this->notificationService->markAllReadForUser($user->id);
//
//        return response()->json(['message' => 'all marked as read']);
//    }
//
//    // GET /api/notifications/unread-count
//    public function unreadCount(Request $request)
//    {
//        $user = $request->user();
//        $count = $this->notificationService->unreadCountForUser($user->id);
//
//        return response()->json([
//            'unread_count' => $count,
//        ]);
//    }
//}
