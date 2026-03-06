<?php

namespace App\Console\Commands;

use App\Services\CommissionService;
use Illuminate\Console\Command;

class CommissionCalculate extends Command
{
    protected $signature = 'commission:calculate {month : Month in format YYYY-MM}';
    protected $description = 'Calculate MLM commissions for a given month';

    public function handle(CommissionService $commissionService): int
    {
        $month = $this->argument('month');

        $this->info("Calculating commissions for month {$month} ...");

        $rows = $commissionService->calculateAndPersist($month);

        $eligibleCount = $rows->where('isEligible', true)->count();
        $this->info("Done. Eligible distributors: {$eligibleCount}");

        return Command::SUCCESS;
    }
}