<?php

namespace HasanHawary\ReportBuilder\Support;

use Illuminate\Support\Str;
use InvalidArgumentException;

class ReportFilterResolver
{
    public function resolve(array $filter): array
    {
        $filter = $this->normalizeEmptyStrings($filter);
        $filter['page'] = $this->resolvePage($filter);
        $filter['apply_date'] = $this->hasFilledValue($filter, 'start') || $this->hasFilledValue($filter, 'end');
        $filter['prefer_chart'] = $this->resolveChartType($filter);

        return $filter;
    }

    private function normalizeEmptyStrings(array $filter): array
    {
        return collect($filter)
            ->map(fn ($value) => $value === '' ? null : $value)
            ->toArray();
    }

    private function resolvePage(array $filter): string
    {
        $page = $filter['page'] ?? config('report.defaults.page');

        if (!is_string($page) || trim($page) === '') {
            throw new InvalidArgumentException('The report page is required.');
        }

        return Str::singular($page);
    }

    private function resolveChartType(array $filter): string
    {
        $chartType = $filter['prefer_chart'] ?? ReportChartType::default();

        if (!in_array($chartType, ReportChartType::values(), true)) {
            throw new InvalidArgumentException("Unsupported report chart type [{$chartType}].");
        }

        return $chartType;
    }

    private function hasFilledValue(array $filter, string $key): bool
    {
        return isset($filter[$key]) && $filter[$key] !== '';
    }
}
