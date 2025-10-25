<?php

namespace HasanHawary\ReportBuilder;

use Illuminate\Support\ServiceProvider;

class ReportBuilderServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Publish the default report.php and chart.php config
        $this->publishes([
            __DIR__ . '/config/report.php' => config_path('report.php'),
            __DIR__ . '/config/chart.php' => config_path('chart.php'),
        ], 'report-builder-config');
    }

    public function register(): void
    {

    }
}
