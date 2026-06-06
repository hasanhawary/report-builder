<?php

namespace HasanHawary\ReportBuilder\Tests\Unit;

use HasanHawary\ReportBuilder\Support\ReportRouteConfig;
use HasanHawary\ReportBuilder\Tests\TestCase;

class ReportRouteConfigTest extends TestCase
{
    public function test_it_normalizes_default_route_config(): void
    {
        $routes = new ReportRouteConfig();

        $this->assertTrue($routes->enabled());
        $this->assertSame('api/report', $routes->prefix());
        $this->assertSame(['api'], $routes->middleware());
        $this->assertSame('report.', $routes->namePrefix());
        $this->assertSame('/', $routes->path('report'));
        $this->assertSame('index', $routes->name('report'));
    }

    public function test_it_normalizes_host_overrides(): void
    {
        config()->set('report.routes.prefix', '/api/report/');
        config()->set('report.routes.middleware', 'api');
        config()->set('report.routes.name_prefix', 'reports');
        config()->set('report.routes.paths.report', '');

        $routes = new ReportRouteConfig();

        $this->assertSame('api/report', $routes->prefix());
        $this->assertSame(['api'], $routes->middleware());
        $this->assertSame('reports.', $routes->namePrefix());
        $this->assertSame('/', $routes->path('report'));
    }

    public function test_it_allows_host_to_disable_route_middleware(): void
    {
        config()->set('report.routes.middleware', []);

        $this->assertSame([], (new ReportRouteConfig())->middleware());
    }
}
