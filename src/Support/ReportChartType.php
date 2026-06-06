<?php

namespace HasanHawary\ReportBuilder\Support;

class ReportChartType
{
    public const HIGH_CHART = 'high_chart';

    public static function default(): string
    {
        $default = config('report.defaults.prefer_chart', self::HIGH_CHART);

        return is_string($default) && in_array($default, self::values(), true)
            ? $default
            : self::HIGH_CHART;
    }

    public static function values(): array
    {
        return app(ChartConfigResolver::class)->supportedProviders();
    }
}
