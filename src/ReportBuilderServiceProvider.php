<?php

namespace HasanHawary\ReportBuilder;

use HasanHawary\ReportBuilder\Support\ReportFilterResolver;
use HasanHawary\ReportBuilder\Support\ReportResponseFactory;
use HasanHawary\ReportBuilder\Support\ReportRouteConfig;
use HasanHawary\ReportBuilder\Support\ChartConfigResolver;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ReportBuilderServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../lang', 'report-builder');
        $this->loadReportBuilderRoutes();

        // Publish the default config files
        $this->publishes([
            __DIR__ . '/../config/report.php' => config_path('report.php'),
            __DIR__ . '/../config/chart.php' => config_path('chart.php'),
        ], 'report-builder-config');

        $translations = [
            __DIR__ . '/../lang' => $this->app->langPath('vendor/report-builder'),
        ];

        $this->publishes($translations, 'report-builder-translations');
        $this->publishes($translations, 'report-builder-lang');
    }

    public function register(): void
    {
        // Merge package config
        $this->mergeConfigFrom(__DIR__ . '/../config/report.php', 'report');
        $this->mergeConfigFrom(__DIR__ . '/../config/chart.php', 'chart');

        $this->app->singleton(ReportRouteConfig::class);
        $this->app->singleton(ReportFilterResolver::class);
        $this->app->singleton(ReportResponseFactory::class);
        $this->app->singleton(ChartConfigResolver::class);
    }

    private function loadReportBuilderRoutes(): void
    {
        $routes = $this->app->make(ReportRouteConfig::class);

        if (!$routes->enabled()) {
            return;
        }

        Route::group([
            'prefix' => $routes->prefix(),
            'middleware' => $routes->middleware(),
            'as' => $routes->namePrefix(),
        ], function (): void {
            $this->loadRoutesFrom(__DIR__ . '/../routes/report-builder.php');
        });
    }
}
