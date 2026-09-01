<?php

use Illuminate\Database\Migrations\Migration;
use Modules\Core\Entities\Menu\CoreMenu;
use Modules\Core\Entities\Project;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Core Menu record update for MOC project
        $project = Project::first();
        $coreMenu = CoreMenu::where(CoreMenu::moduleName, 'offline_package')->first();
        if ($coreMenu && $project->base_project_id == 26) {
            $coreMenu->update([CoreMenu::isShowOnMenu => 1]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $project = Project::first();
        $coreMenu = CoreMenu::where(CoreMenu::moduleName, 'offline_package')->first();
        if ($coreMenu && $project->base_project_id == 26) {
            $coreMenu->update([CoreMenu::isShowOnMenu => 0]);
        }
    }
};
