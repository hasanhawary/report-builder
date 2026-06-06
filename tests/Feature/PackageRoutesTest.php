<?php

namespace HasanHawary\ReportBuilder\Tests\Feature;

use HasanHawary\ReportBuilder\Tests\TestCase;
use Illuminate\Support\Facades\Route;

class PackageRoutesTest extends TestCase
{
    public function test_it_registers_default_package_routes(): void
    {
        $reportRoute = Route::getRoutes()->getByName('report.index');

        $this->assertNotNull($reportRoute);
        $this->assertSame('api/report', $reportRoute->uri());
        $this->assertContains('api', $reportRoute->middleware());
        $this->assertNotContains('auth', $reportRoute->middleware());
        $this->assertNull(Route::getRoutes()->getByName('report.test'));
    }

    public function test_api_report_route_returns_report_response(): void
    {
        $this->getJson('/api/report?page=example')
            ->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.report.cards.data.0.key', 'total')
            ->assertJsonPath('data.report.cards.data.0.value', 5);
    }
}
