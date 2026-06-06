<?php

namespace HasanHawary\ReportBuilder\Support;

use InvalidArgumentException;

class ChartConfigResolver
{
    public function provider(string $provider): array
    {
        $config = config("chart.{$provider}", []);

        if (!is_array($config) || $config === []) {
            throw new InvalidArgumentException("Chart provider [{$provider}] is not configured.");
        }

        return $config;
    }

    public function supportedProviders(): array
    {
        return collect(config('chart', []))
            ->filter(fn ($value, $key) => $key !== 'setting' && is_array($value))
            ->keys()
            ->values()
            ->all();
    }

    public function supportedTypes(array $providerConfig): array
    {
        return array_keys($providerConfig);
    }

    public function chart(array $providerConfig, string $type): array
    {
        $chart = $providerConfig[$type] ?? null;

        if (!is_array($chart)) {
            throw new InvalidArgumentException("Chart type [{$type}] is not configured.");
        }

        return $chart;
    }

    public function primaryColor(): string
    {
        return config('chart.setting.primary_color', 'rgba(var(--v-theme-primary),1)');
    }
}
