<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $r){
        $perPage = (int) $r->query('per_page', 20);

        $rows = Notification::where('user_id', $r->user()->id)
            ->latest('id')
            ->paginate($perPage);

        $rows->getCollection()->transform(fn($n)=>[
            'id'         => $n->id,
            'title'      => $n->title,
            'body'       => $n->body,
            'type'       => $n->type,
            'order_id'   => $n->order_id,
            'data'       => $n->data,
            'read_at'    => $n->read_at?->toIso8601String(),
            'created_at' => $n->created_at->toIso8601String(),
        ]);

        return response()->json($rows);
    }

    public function markRead(Request $r, int $id){
        $n = Notification::where('user_id',$r->user()->id)->findOrFail($id);
        $n->update(['read_at'=>now()]);
        return response()->json(['message'=>'marked as read']);
    }

    public function markAllRead(Request $r){
        Notification::where('user_id',$r->user()->id)->whereNull('read_at')->update(['read_at'=>now()]);
        return response()->json(['message'=>'all marked as read']);
    }
}
