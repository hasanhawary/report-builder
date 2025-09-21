<?php

namespace HasanHawary\ReportBuilder;

use Illuminate\Support\ServiceProvider;

class ReportBuilderServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Publish the default config files
        $this->publishes([
            __DIR__ . '/../config/report.php' => config_path('report.php'),
            __DIR__ . '/../config/chart.php' => config_path('chart.php'),
        ], 'report-builder-config');

        // Publish translation files from the package root lang folder
        $this->publishes([
            __DIR__ . '/../lang' => resource_path('lang/vendor/report-builder'),
        ], 'report-builder-lang');

        // Load translations from package
        $this->loadTranslationsFrom(__DIR__ . '/../../lang', 'report-builder');
    }

    public function register(): void
    {
        // Merge package config
        $this->mergeConfigFrom(__DIR__ . '/../config/report.php', 'report');
        $this->mergeConfigFrom(__DIR__ . '/../config/chart.php', 'chart');
    }
}
