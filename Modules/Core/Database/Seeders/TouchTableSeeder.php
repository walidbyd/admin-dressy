<?php

namespace Modules\Core\Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TouchTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        for ($x = 0; $x <= 7; $x += 1) {
            DB::table('psx_touches')->insert([
                'type_id' => $x + 1,
                'user_id' => 1,
                'shop_id' => '1',
                'type_name' => 'Category',
                'added_user_id' => 1,
                'added_date' => Carbon::now(),
            ]);
        }

        DB::table('psx_touches')->insert([
            'type_id' => 1,
            'user_id' => 1,
            'shop_id' => '1',
            'type_name' => 'Category',
            'added_user_id' => 1,
            'added_date' => Carbon::now(),
        ]);

        for ($x = 0; $x <= 24; $x += 1) {
            DB::table('psx_touches')->insert([
                'type_id' => $x + 1,
                'user_id' => 1,
                'shop_id' => '1',
                'type_name' => 'Subcategory',
                'added_user_id' => 1,
                'added_date' => Carbon::now(),
            ]);
        }

        DB::table('psx_touches')->insert([
            'type_id' => 2,
            'user_id' => 1,
            'shop_id' => '1',
            'type_name' => 'Subcategory',
            'added_user_id' => 1,
            'added_date' => Carbon::now(),
        ]);
    }
}
