<?php

namespace HasanHawary\ReportBuilder\Tests\Unit;

use HasanHawary\ReportBuilder\Support\ReportFilterResolver;
use HasanHawary\ReportBuilder\Tests\TestCase;
use InvalidArgumentException;

class ReportFilterResolverTest extends TestCase
{
    public function test_it_resolves_canonical_filter_defaults(): void
    {
        config()->set('report.defaults.page', 'user');

        $filter = app(ReportFilterResolver::class)->resolve([
            'start' => '',
            'end' => '2026-06-05',
        ]);

        $this->assertSame('user', $filter['page']);
        $this->assertTrue($filter['apply_date']);
        $this->assertSame('high_chart', $filter['prefer_chart']);
        $this->assertNull($filter['start']);
    }

    public function test_it_requires_a_page_when_no_default_exists(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The report page is required.');

        app(ReportFilterResolver::class)->resolve([]);
    }

    public function test_it_rejects_unknown_chart_provider(): void
    {
        config()->set('report.defaults.page', 'user');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported report chart type [missing].');

        app(ReportFilterResolver::class)->resolve([
            'prefer_chart' => 'missing',
        ]);
    }
}
