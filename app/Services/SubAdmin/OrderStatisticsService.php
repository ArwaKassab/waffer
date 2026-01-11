<?php


namespace App\Services\SubAdmin;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;

class OrderStatisticsService
{
    public function getStatistics(string $from, string $to, bool $withTrashed = false, int $perPage): array
    {
        $fromDt = Carbon::createFromFormat('Y-m-d', $from);
        $toDt   = Carbon::createFromFormat('Y-m-d', $to);

        $baseQuery = Order::query()
            ->with(['user:id,name,phone'])
            ->when($withTrashed, fn ($q) => $q->withTrashed())
            ->whereBetween('created_at', [$fromDt, $toDt]);

        // مجموع وعدد (بدون pagination)
        $ordersCount = (clone $baseQuery)->count();
        $ordersTotalSum = (clone $baseQuery)->sum('total_price');

        // تجميعة حسب الحالة (اختياري ومفيد للداشبورد)
        $byStatus = (clone $baseQuery)
            ->selectRaw('status, COUNT(*) as count, SUM(total_price) as total_sum')
            ->groupBy('status')
            ->orderBy('status')
            ->get()
            ->map(fn ($row) => [
                'status'    => $row->status,
                'count'     => (int) $row->count,
                'total_sum' => (float) $row->total_sum,
            ])
            ->values();

        /** @var LengthAwarePaginator $ordersPage */
        $ordersPage = (clone $baseQuery)
            ->select(['id', 'user_id', 'status', 'payment_method', 'total_price', 'date', 'time', 'created_at'])
            ->orderByDesc('created_at')
            ->paginate($perPage);

        $ordersTransformed = collect($ordersPage->items())->map(function ($order) {
            return [
                'order_id'        => $order->id,
                'user_name'       => $order->user?->name,
                'user_phone'      => $order->user?->phone,

                'status'          => $order->status,
                'payment_method'  => $order->payment_method,

                'invoice_total'   => (float) $order->total_price,

                // لإظهارهم كما طلبت (تاريخ ووقت)
                'date'            => $order->date,
                'time'            => $order->time,
                'created_at'      => optional($order->created_at)->format('Y-m-d H:i:s'),
            ];
        })->values();

        return [
            'range' => [
                'from' => $fromDt->format('Y-m-d H:i:s'),
                'to'   => $toDt->format('Y-m-d H:i:s'),
            ],
            'orders_count'     => $ordersCount,
            'orders_total_sum' => (float) $ordersTotalSum,

            'by_status' => $byStatus,

            // pagination meta + data
            'orders' => [
                'data' => $ordersTransformed,
                'meta' => [
                    'current_page' => $ordersPage->currentPage(),
                    'per_page'     => $ordersPage->perPage(),
                    'last_page'    => $ordersPage->lastPage(),
                    'total'        => $ordersPage->total(),
                ],
            ],
        ];
    }
}
