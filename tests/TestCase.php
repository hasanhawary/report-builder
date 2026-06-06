<?php

namespace HasanHawary\ReportBuilder\Tests;

use HasanHawary\ReportBuilder\ReportBuilderServiceProvider;
use HasanHawary\ReportBuilder\Tests\Fixtures\ExampleReport;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            ReportBuilderServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('report.pages.example', [
            'class' => ExampleReport::class,
            'report' => [
                'cards' => [
                    'type' => 'card',
                    'size' => ['cols' => '12'],
                ],
            ],
        ]);
    }
}
