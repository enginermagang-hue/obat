<?php

namespace Tests\Feature;

use App\Models\SetupConfiguration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SetupWizardTest extends TestCase
{
    use RefreshDatabase;

    public function test_setup_wizard_page_loads_if_setup_not_completed(): void
    {
        $response = $this->get('/admin/setup-wizard');

        $response->assertOk();
    }

    public function test_setup_wizard_redirects_if_setup_completed(): void
    {
        SetupConfiguration::create([
            'is_setup_completed' => true,
            'organization_name' => 'Test Org',
            'setup_completed_at' => now(),
        ]);

        $response = $this->get('/admin/setup-wizard');

        $response->assertRedirect('/admin');
    }

    public function test_setup_configuration_model_methods(): void
    {
        $this->assertFalse(SetupConfiguration::isSetupCompleted());

        $config = SetupConfiguration::getConfig();
        $this->assertNotNull($config);

        $config->markSetupCompleted();
        $this->assertTrue(SetupConfiguration::isSetupCompleted());
    }
}
