<?php

namespace HasanHawary\ReportBuilder\Tests\Unit;

use HasanHawary\ReportBuilder\Support\ChartConfigResolver;
use HasanHawary\ReportBuilder\Tests\TestCase;
use InvalidArgumentException;

class ChartConfigResolverTest extends TestCase
{
    public function test_it_owns_chart_provider_and_type_lookup(): void
    {
        $resolver = app(ChartConfigResolver::class);
        $provider = $resolver->provider('high_chart');

        $this->assertContains('high_chart', $resolver->supportedProviders());
        $this->assertContains('pie', $resolver->supportedTypes($provider));
        $this->assertIsArray($resolver->chart($provider, 'pie'));
        $this->assertSame('rgba(var(--v-theme-primary),1)', $resolver->primaryColor());
    }

    public function test_it_fails_clearly_for_missing_chart_provider(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Chart provider [missing] is not configured.');

        app(ChartConfigResolver::class)->provider('missing');
    }

    public function test_it_fails_clearly_for_missing_chart_type(): void
    {
        $resolver = app(ChartConfigResolver::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Chart type [missing] is not configured.');

        $resolver->chart($resolver->provider('high_chart'), 'missing');
    }
}
