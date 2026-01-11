<?php


namespace App\Http\Controllers\SubAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\OrderStatisticsRequest;
use App\Services\SubAdmin\OrderStatisticsService;

class OrderStatisticsController extends Controller
{
    public function __construct(private readonly OrderStatisticsService $service)
    {
    }

    public function index(OrderStatisticsRequest $request)
    {
        $data = $this->service->getStatistics(
            from: $request->input('from'),
            to: $request->input('to'),
            withTrashed: (bool) $request->boolean('with_trashed', false),
            perPage: (int) $request->input('per_page'),
        );

        return response()->json($data);
    }
}
