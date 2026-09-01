<?php

namespace Modules\Core\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Modules\Core\Entities\Favourite;
use Modules\Core\Entities\Item;
use Modules\Core\Entities\Touch;

class CoreDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        // seeding data generate
        $this->call([
            UsersTableSeeder::class,
            BackendSettingTableSeeder::class,
            ItemsTableSeeder::class,
            ImagesTableSeeder::class,
            CategoriesTableSeeder::class,
            SubcategoriesTableSeeder::class,
            AdPostTypesTableSeeder::class,
            CitiesTableSeeder::class,
            CurrenciesTableSeeder::class,
            LanguagesTableSeeder::class,
            LanguageStringsTableSeeder::class,
            MenuGroupsTableSeeder::class,
            SubMenuGroupsTableSeeder::class,
            ModulesTableSeeder::class,
            AboutTableSeeder::class,
            PaymentStatusesTableSeeder::class,
            ShippingsTableSeeder::class,
            ShopsTableSeeder::class,
            TownshipsTableSeeder::class,
            TransactionStatusesTableSeeder::class,
            TransactionHeadersTableSeeder::class,
            TransactionDetailsTableSeeder::class,
            TransactionCountsTableSeeder::class,
            UiTypeTableSeeder::class,
            RoleTableSeeder::class,
            PermissionTableSeeder::class,
            TouchTableSeeder::class,
            UserInfoTableSeeder::class,
            CoreKeyTableSeeder::class,
            CoreKeyTypeTableSeeder::class,
            // CustomizeUiTableSeeder::class,
            CustomizeUiDetailTableSeeder::class,
            // CoreFieldFilterSettingTableSeeder::class,
            // ScreenDisplayUiSettingTableSeeder::class,
            MobileLanaguageTableSeeder::class,
            MobileLanaguageStringTableSeeder::class,
            UserPermissionTableSeeder::class,
            RolePermissionTableSeeder::class,
            BlogTableSeeder::class,
            PersonalAccessTokenTableSeeder::class,
            PrivacyPolicyTableSeeder::class,
            AvailableCurrencyTableSeeder::class,
            MobileSettingTableSeeder::class,
            FrontendSettingTableSeeder::class,
            SystemConfigTableSeeder::class,
            TableUsedTypeTableSeeder::class,
            PhoneCountryCodeTableSeeder::class,
        ]);

        // factory data generate
        User::factory()->count(50)->create();
        Item::factory()->count(50)->create();
        Touch::factory()->count(50)->create();
        Favourite::factory()->count(50)->create();
    }
}
