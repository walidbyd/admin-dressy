<?php

namespace Modules\Payment\Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CoreKeyPaymentRelationTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        DB::table('psx_core_key_payment_relations')->insert([
            'payment_id' => 'payment00001',
            'core_keys_id' => 'pmt00001',
            'added_user_id' => '1',
            'added_date' => Carbon::now(),
        ]);

        DB::table('psx_core_key_payment_relations')->insert([
            'payment_id' => 'payment00001',
            'core_keys_id' => 'pmt00002',
            'added_user_id' => '1',
            'added_date' => Carbon::now(),
        ]);

        DB::table('psx_core_key_payment_relations')->insert([
            'payment_id' => 'payment00001',
            'core_keys_id' => 'pmt00003',
            'added_user_id' => '1',
            'added_date' => Carbon::now(),
        ]);

        DB::table('psx_core_key_payment_relations')->insert([
            'payment_id' => 'payment00001',
            'core_keys_id' => 'pmt00004',
            'added_user_id' => '1',
            'added_date' => Carbon::now(),
        ]);

        DB::table('psx_core_key_payment_relations')->insert([
            'payment_id' => 'payment00001',
            'core_keys_id' => 'pmt00005',
            'added_user_id' => '1',
            'added_date' => Carbon::now(),
        ]);

        DB::table('psx_core_key_payment_relations')->insert([
            'payment_id' => 'payment00001',
            'core_keys_id' => 'pmt00006',
            'added_user_id' => '1',
            'added_date' => Carbon::now(),
        ]);

        DB::table('psx_core_key_payment_relations')->insert([
            'payment_id' => 'payment00002',
            'core_keys_id' => 'pmt00012',
            'added_user_id' => '1',
            'added_date' => Carbon::now(),
        ]);

        DB::table('psx_core_key_payment_relations')->insert([
            'payment_id' => 'payment00002',
            'core_keys_id' => 'pmt00013',
            'added_user_id' => '1',
            'added_date' => Carbon::now(),
        ]);

        DB::table('psx_core_key_payment_relations')->insert([
            'payment_id' => 'payment00003',
            'core_keys_id' => 'pmt00014',
            'added_user_id' => '1',
            'added_date' => Carbon::now(),
        ]);

        DB::table('psx_core_key_payment_relations')->insert([
            'payment_id' => 'payment00004',
            'core_keys_id' => 'pmt00015',
            'added_user_id' => '1',
            'added_date' => Carbon::now(),
        ]);

        // =========================================
        DB::table('payment_infos')->insert([
            'payment_id' => 'payment00001',
            'core_keys_id' => 'pmt00001',
            'value' => 'h6ggypvjgt4tzz2k',
            'added_user_id' => '1',
            'added_date' => Carbon::now(),
        ]);

        DB::table('payment_infos')->insert([
            'payment_id' => 'payment00001',
            'core_keys_id' => 'pmt00002',
            'value' => '256y6grqr936tpjf',
            'added_user_id' => '1',
            'added_date' => Carbon::now(),
        ]);

        DB::table('payment_infos')->insert([
            'payment_id' => 'payment00001',
            'core_keys_id' => 'pmt00003',
            'value' => '0d6aadf4b586a84844ceadc834dc851f',
            'added_user_id' => '1',
            'added_date' => Carbon::now(),
        ]);

        DB::table('payment_infos')->insert([
            'payment_id' => 'payment00001',
            'core_keys_id' => 'pmt00004',
            'value' => '',
            'added_user_id' => '1',
            'added_date' => Carbon::now(),
        ]);

        DB::table('payment_infos')->insert([
            'payment_id' => 'payment00001',
            'core_keys_id' => 'pmt00005',
            'value' => '',
            'added_user_id' => '1',
            'added_date' => Carbon::now(),
        ]);

        DB::table('payment_infos')->insert([
            'payment_id' => 'payment00001',
            'core_keys_id' => 'pmt00006',
            'value' => 'sandbox',
            'added_user_id' => '1',
            'added_date' => Carbon::now(),
        ]);

        DB::table('payment_infos')->insert([
            'payment_id' => 'payment00002',
            'core_keys_id' => 'pmt00012',
            'value' => 'pk_test_RjJFXdLkbesQpjm6T2xPddmp',
            'added_user_id' => '1',
            'added_date' => Carbon::now(),
        ]);

        DB::table('payment_infos')->insert([
            'payment_id' => 'payment00002',
            'core_keys_id' => 'pmt00013',
            'value' => 'sk_test_lxHim6W6aJAjb4jjAtfviY0t',
            'added_user_id' => '1',
            'added_date' => Carbon::now(),
        ]);

        DB::table('payment_infos')->insert([
            'payment_id' => 'payment00003',
            'core_keys_id' => 'pmt00014',
            'value' => 'rzp_test_Bpvf8XjEecGumH',
            'added_user_id' => '1',
            'added_date' => Carbon::now(),
        ]);

        DB::table('payment_infos')->insert([
            'payment_id' => 'payment00004',
            'core_keys_id' => 'pmt00015',
            'value' => 'pk_test_ea5593678cc80271640f1929b5a63ac830dd66f8',
            'added_user_id' => '1',
            'added_date' => Carbon::now(),
        ]);
    }
}
