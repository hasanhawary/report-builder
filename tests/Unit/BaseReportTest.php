<?php

namespace HasanHawary\ReportBuilder\Tests\Unit;

use HasanHawary\ReportBuilder\Exceptions\ReportComponentException;
use HasanHawary\ReportBuilder\ReportBuilder;
use HasanHawary\ReportBuilder\Tests\Fixtures\ExampleReport;
use HasanHawary\ReportBuilder\Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BaseReportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('report.pages.example', [
            'type' => 'page',
            'class' => ExampleReport::class,
            'report' => [
                'cards' => [
                    'type' => 'card',
                    'size' => ['cols' => '6', 'md' => '3', 'lg' => '3'],
                ],
                'broken_chart' => [
                    'type' => 'spline',
                ],
                'chart' => [
                    'type' => 'spline',
                ],
            ],
        ]);
    }

    public function test_it_executes_components_with_explicit_component_key(): void
    {
        config()->set('report.component_errors', 'omit');

        $report = new ExampleReport([
            'page' => 'example',
            'prefer_chart' => 'high_chart',
            'types' => ['cards', 'broken_chart'],
        ]);

        $result = $report->report();

        $this->assertSame('cards', $result['report']['cards']['key']);
        $this->assertSame('total', $result['report']['cards']['data'][0]['key']);
        $this->assertArrayNotHasKey('charts', $result['report']);
    }

    public function test_it_throws_component_failures_by_default(): void
    {
        $report = new ExampleReport([
            'page' => 'example',
            'prefer_chart' => 'high_chart',
            'types' => ['broken_chart'],
        ]);

        $this->expectException(ReportComponentException::class);
        $this->expectExceptionMessage('Report component [broken_chart] failed: Broken chart');

        $report->report();
    }

    public function test_it_uses_one_query_to_guess_date_format_range(): void
    {
        Schema::create('examples_for_dates', function ($table): void {
            $table->id();
            $table->timestamp('created_at')->nullable();
        });

        DB::table('examples_for_dates')->insert([
            ['created_at' => '2026-06-01 00:00:00'],
            ['created_at' => '2026-06-05 00:00:00'],
        ]);

        $report = new ExampleReport([
            'page' => 'example',
            'prefer_chart' => 'high_chart',
        ]);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $format = $report->guessDateFormat('examples_for_dates', 'created_at');

        $this->assertSame('%d-%b-%Y', $format);
        $this->assertCount(1, DB::getQueryLog());
    }

    public function test_it_caches_soft_delete_column_checks_per_table(): void
    {
        Schema::create('examples_for_soft_deletes', function ($table): void {
            $table->id();
            $table->timestamp('deleted_at')->nullable();
        });

        $report = new ExampleReport([
            'page' => 'example',
            'prefer_chart' => 'high_chart',
        ]);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $report->checkSoftDelete(DB::table('examples_for_soft_deletes'), 'examples_for_soft_deletes');
        $queryCountAfterFirstCall = count(DB::getQueryLog());

        $report->checkSoftDelete(DB::table('examples_for_soft_deletes'), 'examples_for_soft_deletes');

        $this->assertSame($queryCountAfterFirstCall, count(DB::getQueryLog()));
    }

    public function test_report_builder_resolves_page_specific_report_class_without_app_namespace(): void
    {
        $result = (new ReportBuilder([
            'page' => 'example',
            'prefer_chart' => 'high_chart',
            'types' => ['cards'],
        ]))->response();

        $this->assertSame('cards', $result['report']['cards']['key']);
    }

    public function test_it_resolves_report_labels_from_configured_translation_file(): void
    {
        config()->set('report.translate.trans_file', 'custom_report');
        app('translator')->addLines([
            'custom_report.example_report' => 'Example analytics',
            'custom_report.cards' => 'Summary cards',
            'custom_report.total' => 'Total records',
            'custom_report.active' => 'Active users',
            'custom_report.users_count' => 'Users count',
        ], app()->getLocale());

        $result = (new ReportBuilder([
            'page' => 'example',
            'prefer_chart' => 'high_chart',
            'types' => ['cards', 'chart'],
        ]))->response();

        $this->assertSame('Example analytics', $result['report']['title']);
        $this->assertSame('Summary cards', $result['report']['cards']['title']);
        $this->assertSame('Total records', $result['report']['cards']['data'][0]['label']);
        $this->assertSame('Active users', $result['report']['charts'][0]['data']['spline']['xAxis']['categories'][0]);
        $this->assertSame('Users count', $result['report']['charts'][0]['data']['spline']['series'][0]['name']);
    }

    public function test_it_can_disable_report_translation(): void
    {
        config()->set('report.translate.enabled', false);
        config()->set('report.translate.trans_file', 'custom_report');
        app('translator')->addLines([
            'custom_report.total' => 'Total records',
        ], app()->getLocale());

        $result = (new ReportBuilder([
            'page' => 'example',
            'prefer_chart' => 'high_chart',
            'types' => ['cards'],
        ]))->response();

        $this->assertSame('total', $result['report']['cards']['data'][0]['label']);
    }
}
