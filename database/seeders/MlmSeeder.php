<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MlmSeeder extends Seeder
{
    public function run(): void
    {

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        DB::table('orders')->truncate();
        DB::table('commissions')->truncate();
        DB::table('users')->truncate();

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $a = User::create(['name' => 'A']);
        $b = User::create(['name' => 'B', 'parent_id' => $a->id]);
        $c = User::create(['name' => 'C', 'parent_id' => $a->id]);

        $d = User::create(['name' => 'D', 'parent_id' => $b->id]);
        $e = User::create(['name' => 'E', 'parent_id' => $b->id]);

        $f = User::create(['name' => 'F', 'parent_id' => $c->id]);
        $g = User::create(['name' => 'G', 'parent_id' => $c->id]);

        $h = User::create(['name' => 'H', 'parent_id' => $d->id]);
        $i = User::create(['name' => 'I', 'parent_id' => $e->id]);
        $j = User::create(['name' => 'J', 'parent_id' => $f->id]);

        $users = User::all();

        $now = Carbon::now()->startOfMonth();

        foreach (range(0, 11) as $monthOffset) {
            $monthDate = (clone $now)->subMonths($monthOffset);

            foreach ($users as $user) {
                $orderCount = rand(3, 8);

                for ($k = 0; $k < $orderCount; $k++) {
                    $day = rand(1, 25);
                    $createdAt = (clone $monthDate)->day($day);

                    $amount = rand(500_000, 80_000_000);

                    Order::create([
                        'user_id' => $user->id,
                        'amount' => $amount,
                        'created_at' => $createdAt,
                    ]);
                }
            }
        }
    }
}
