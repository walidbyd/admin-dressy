<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Modules\Core\Entities\Project;
use Modules\Theme\Entities\ComponentAttribute;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        try {
            Artisan::call('optimize:clear');
            $project = Project::first();

            if (isset($project)) {
                $baseData = [
                    'theme_id' => 1,
                    'attributes' => '{"is_show":"0"}',
                    'added_date' => now(),
                    'added_user_id' => 0,
                ];
                if ($project->base_project_id == 11) {
                    $components = $this->getMPCcomponent();
                }
                if ($project->base_project_id == 26) {
                    $components = $this->getMOCcomponent();
                }
                if ($project->base_project_id == 27) {
                    $components = $this->getRECcomponent();
                }
                if ($project->base_project_id == 28) {
                    $components = $this->getCGCcomponent();
                } else {
                    $components = $this->getMPCcomponent();
                }

                foreach ($components as $component) {
                    ComponentAttribute::create([
                        ...$component,
                        ...$baseData,
                    ]);
                }
            }
        } catch (Throwable $e) {
            throw $e;
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {}

    /**
     * MPC
     */
    private function getMPCcomponent()
    {
        return [
            [
                'name' => 'Item Vertical List with Filter Component',
                'component_id' => 'ps-cweb00050',
                'platform_id' => 1,
                'screen_id' => 'ps-sweb00001',
            ],
            [
                'name' => 'Item Vertical List Component ( Filter Nav )',
                'component_id' => 'ps-cmb00046',
                'platform_id' => 2,
                'screen_id' => 'ps-smb00001',
            ],
            [
                'name' => 'Item Vertical List Component ( List )',
                'component_id' => 'ps-cmb00047',
                'platform_id' => 2,
                'screen_id' => 'ps-smb00001',
            ],
        ];
    }

    /**
     * MOC
     */
    private function getMOCcomponent()
    {
        return [
            [
                'name' => 'Item Vertical List with Filter Component',
                'component_id' => 'ps-cweb00052',
                'platform_id' => 1,
                'screen_id' => 'ps-sweb00003',
            ],
            [
                'name' => 'Item Vertical List Component ( Filter Nav )',
                'component_id' => 'ps-cmb00048',
                'platform_id' => 2,
                'screen_id' => 'ps-smb00002',
            ],
            [
                'name' => 'Item Vertical List Component ( List )',
                'component_id' => 'ps-cmb00049',
                'platform_id' => 2,
                'screen_id' => 'ps-smb00002',
            ],
        ];
    }

    /**
     * REC
     */
    private function getRECcomponent()
    {
        return [
            [
                'name' => 'Item Vertical List with Filter Component',
                'component_id' => 'ps-cweb00053',
                'platform_id' => 1,
                'screen_id' => 'ps-sweb00004',
            ],
            [
                'name' => 'Item Vertical List Component ( Filter Nav )',
                'component_id' => 'ps-cmb00050',
                'platform_id' => 2,
                'screen_id' => 'ps-smb00003',
            ],
            [
                'name' => 'Item Vertical List Component ( List )',
                'component_id' => 'ps-cmb00051',
                'platform_id' => 2,
                'screen_id' => 'ps-smb00003',
            ],
        ];
    }

    /**
     * CGC
     */
    private function getCGCcomponent()
    {
        return [
            [
                'name' => 'Item Vertical List with Filter Component',
                'component_id' => 'ps-cweb00051',
                'platform_id' => 1,
                'screen_id' => 'ps-sweb00002',
            ],
            [
                'name' => 'Item Vertical List Component ( Filter Nav )',
                'component_id' => 'ps-cmb00052',
                'platform_id' => 2,
                'screen_id' => 'ps-smb00004',
            ],
            [
                'name' => 'Item Vertical List Component ( List )',
                'component_id' => 'ps-cmb00053',
                'platform_id' => 2,
                'screen_id' => 'ps-smb00003',
            ],
        ];
    }
};
