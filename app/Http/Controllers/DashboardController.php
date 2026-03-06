<?php

namespace App\Http\Controllers;

use App\Services\CommissionService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request, CommissionService $commissionService)
    {
        $selectedMonth = $request->query('month', Carbon::now()->format('Y-m'));

        $report = $commissionService->buildReport($selectedMonth, true);

        $monthOptions = collect(range(0, 5))->map(function ($offset) {
            $month = now()->startOfMonth()->subMonths($offset);
            return [
                'value' => $month->format('Y-m'),
                'label' => $month->format('m/Y'),
            ];
        });

        return view('dashboard', [
            'selectedMonth' => $selectedMonth,
            'pool'          => $report['pool'],
            'rows'          => $report['rows'],
            'monthOptions'  => $monthOptions,
        ]);
    }
}