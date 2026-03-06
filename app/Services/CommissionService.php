<?php

namespace App\Services;

use App\Models\Commission;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CommissionService
{
	private const PERSONAL_SALES_THRESHOLD = 5_000_000;
	private const BRANCH_SALES_THRESHOLD = 250_000_000;
	private const QUALIFICATION_MONTHS = 3;
	private const LOSS_FAIL_COUNT = 5;

	/**
	 * @param string $monthString month in format YYYY-MM
	 * @return Collection list of per-user report rows
	 */
	public function calculateAndPersist(string $monthString): Collection
	{
		$report = $this->buildReport($monthString, true);

		/** @var Collection $rows */
		$rows = $report['rows'];
		$targetMonth = Carbon::createFromFormat('Y-m', $monthString)->startOfMonth();

		foreach ($rows as $row) {
			if (!$row['isEligible'] || $row['commission'] <= 0) {
				continue;
			}

			Commission::updateOrCreate(
				[
					'user_id' => $row['user']->id,
					'month'   => $targetMonth->toDateString(),
				],
				[
					'reward' => $row['commission'],
				]
			);
		}

		return $rows;
	}

	/**
	 * @param string $monthString
	 * @param bool $shouldCalculateRewards whether to compute reward
	 * @return array{month: Carbon, pool: float, rows: Collection}
	 */
	public function buildReport(string $monthString, bool $shouldCalculateRewards = false): array
	{
		$targetMonth = Carbon::createFromFormat('Y-m', $monthString)->startOfMonth();

		$months = collect(range(0, self::QUALIFICATION_MONTHS - 1))
			->map(fn($offset) => (clone $targetMonth)->subMonths($offset))
			->sort();

		$failureCountMonths = collect(range(0, 11))
			->map(fn($offset) => (clone $targetMonth)->subMonths($offset))
			->sort();

		$minDate = $failureCountMonths->first()->copy()->startOfMonth();
		$maxDate = $months->last()->copy()->endOfMonth();

		$users = User::with('children')->get()->keyBy('id');

		$childrenMap = [];
		foreach ($users as $user) {
			$childrenMap[$user->parent_id ?? 0][] = $user->id;
		}

		$personalSales = [];

		$orderRows = Order::query()
			->selectRaw('user_id, DATE_FORMAT(created_at, "%Y-%m") as ym, SUM(amount) as total')
			->whereBetween('created_at', [$minDate, $maxDate])
			->groupBy('user_id', 'ym')
			->get();

		foreach ($orderRows as $row) {
			$ym = $row->ym;
			$userId = (int) $row->user_id;

			$personalSales[$ym][$userId] = ($personalSales[$ym][$userId] ?? 0) + (float) $row->total;
		}

		$allMonthsFailures = [];
		foreach ($users as $user) {
			$failCount = 0;
			foreach ($failureCountMonths as $month) {
				$ym = $month->format('Y-m');
				$amount = $personalSales[$ym][$user->id] ?? 0;
				if ($amount < self::PERSONAL_SALES_THRESHOLD) {
					$failCount++;
				}
			}
			$allMonthsFailures[$user->id] = $failCount;
		}

		$branchSales = [];
		foreach ($months as $month) {
			$ym = $month->format('Y-m');

			foreach ($users as $user) {
				$branchSales[$ym][$user->id] = $this->calculateBranchSales(
					$user->id,
					$ym,
					$personalSales,
					$childrenMap
				);
			}
		}

		$targetYm = $targetMonth->format('Y-m');
		$systemRevenue = array_sum($personalSales[$targetYm] ?? []);
		$pool = $systemRevenue * 0.01;

		$rows = collect();

		$eligibleUserIds = [];
		foreach ($users as $user) {
			$personalThreeMonths = [];
			foreach ($months as $month) {
				$ym = $month->format('Y-m');
				$personalThreeMonths[$ym] = $personalSales[$ym][$user->id] ?? 0;
			}

			$personalQualified = collect($personalThreeMonths)
				->every(fn($amount) => $amount >= self::PERSONAL_SALES_THRESHOLD);

			$qualifiedBranchesCount = $this->countQualifiedBranches(
				$user->id,
				$months,
				$personalSales,
				$childrenMap
			);

			$branchQualified = $qualifiedBranchesCount >= 2;

			$lostTitle = $allMonthsFailures[$user->id] >= self::LOSS_FAIL_COUNT;

			$isEligible = $personalQualified && $branchQualified && !$lostTitle;

			if ($isEligible) {
				$eligibleUserIds[] = $user->id;
			}

			$rows->push([
				'user'                => $user,
				'personalSales'       => $personalSales[$targetYm][$user->id] ?? 0.0,
				'branchSales'         => $branchSales[$targetYm][$user->id] ?? 0.0,
				'qualifiedBranches'   => $qualifiedBranchesCount,
				'isEligible'          => $isEligible,
				'commission'          => 0.0,
			]);
		}

		$eligibleCount = count($eligibleUserIds);
		$commissionPerUser = ($eligibleCount > 0 && $shouldCalculateRewards)
			? $pool / $eligibleCount
			: 0.0;

		if ($shouldCalculateRewards && $eligibleCount > 0) {
			$rows = $rows->map(function (array $row) use ($commissionPerUser) {
				if ($row['isEligible']) {
					$row['commission'] = $commissionPerUser;
				}

				return $row;
			});
		}

		return [
			'month' => $targetMonth,
			'pool'  => $pool,
			'rows'  => $rows,
		];
	}

	private function calculateBranchSales(
		int $userId,
		string $ym,
		array $personalSales,
		array $childrenMap
	): float {
		$total = 0.0;

		$children = $childrenMap[$userId] ?? [];
		foreach ($children as $childId) {
			$total += $personalSales[$ym][$childId] ?? 0.0;
			$total += $this->calculateBranchSales($childId, $ym, $personalSales, $childrenMap);
		}

		return $total;
	}

	private function countQualifiedBranches(
		int $userId,
		Collection $months,
		array $personalSales,
		array $childrenMap
	): int {

		$qualified = 0;

		$children = $childrenMap[$userId] ?? [];

		$targetMonth = $months->last()->format('Y-m');

		foreach ($children as $childId) {

			$sales =
				($personalSales[$targetMonth][$childId] ?? 0.0) +
				$this->calculateBranchSales($childId, $targetMonth, $personalSales, $childrenMap);

			if ($sales >= self::BRANCH_SALES_THRESHOLD) {
				$qualified++;
			}
		}

		return $qualified;
	}
}
