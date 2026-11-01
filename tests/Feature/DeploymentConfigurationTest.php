<?php

namespace Tests\Feature;

use App\Providers\AppServiceProvider;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class DeploymentConfigurationTest extends TestCase
{
    public function test_private_storage_disks_throw_and_report_failures(): void
    {
        $this->assertTrue(config('filesystems.disks.local.throw'));
        $this->assertTrue(config('filesystems.disks.local.report'));
        $this->assertTrue(config('filesystems.disks.s3.throw'));
        $this->assertTrue(config('filesystems.disks.s3.report'));
    }

    public function test_render_blueprint_keeps_public_onrender_subdomain_enabled(): void
    {
        $blueprint = (string) file_get_contents(base_path('render.yaml'));

        $this->assertStringContainsStringIgnoringLineEndings(
            "    renderSubdomainPolicy: enabled\n",
            $blueprint,
        );
    }

    public function test_trusted_proxy_headers_are_used_for_generated_redirects(): void
    {
        $this->withHeaders([
            'X-Forwarded-Host' => 'tracker.example.com',
            'X-Forwarded-Port' => '443',
            'X-Forwarded-Proto' => 'https',
        ])->get('/')->assertRedirect('https://tracker.example.com/login');
    }

    public function test_force_https_configuration_forces_generated_urls(): void
    {
        config()->set('app.force_https', true);

        try {
            (new AppServiceProvider($this->app))->boot();

            $this->assertStringStartsWith('https://', route('login'));
        } finally {
            URL::forceScheme(null);
        }
    }
}
