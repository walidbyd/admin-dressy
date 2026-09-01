<?php

namespace Modules\Core\Tests\Unit;

use App\Console\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Foundation\Testing\Traits\CanConfigureMigrationCommands;
use Tests\TestCase;

class CleanTest extends TestCase
{
    use CanConfigureMigrationCommands;

    public function test_clean()
    {
        if (! RefreshDatabaseState::$migrated) {
            $this->artisan('migrate:fresh', $this->migrateFreshUsing());

            $this->app[Kernel::class]->setArtisan(null);

            RefreshDatabaseState::$migrated = true;
        }

        $this->assertTrue(true);
    }
}
