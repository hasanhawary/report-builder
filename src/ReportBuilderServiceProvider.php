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
    }

    public function register(): void
    {
        // Merge package config
        $this->mergeConfigFrom(__DIR__ . '/../config/report.php', 'report');
        $this->mergeConfigFrom(__DIR__ . '/../config/chart.php', 'chart');

         // Load helpers
        if (file_exists(__DIR__ . '/helpers.php')) {
            require_once __DIR__ . '/helpers.php';
        }
    }
}
