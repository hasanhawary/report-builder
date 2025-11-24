<?php

namespace HasanHawary\ReportBuilder;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ChartHandler
{
    private array $chart;

    public function __construct($type)
    {
        $this->chart = config("chart.$type", []);
    }

    /**
     * Prepare chart data for various chart types.
     *
     * Notes:
     * - If $chartType is 'table', the original data is returned unchanged (for tabular grids).
     * - If $chartType is 'all', an array keyed by each supported chart type will be returned.
     * - Collections are accepted and will be converted to arrays.
     *
     * @param string $groupByField The name of the groupBy field in the input data.
     * @param array|Collection $data The input data.
     * @param string|null $chartType The type of chart ('bar', 'line', 'pie','table','all').
     * @return array                 The prepared chart data.
     */
    public function resolve(string $groupByField, array|Collection $data = [], ?string $chartType = 'all'): array
    {
        // Normalize to array
        $data = is_array($data) ? $data : $data->toArray();

        // Directly return data if chart type is 'table'
        if ($chartType === 'table') {
            return $data;
        }

        // Process the input data and format it into categories and series
        [$categories, $series] = $this->formatStanderSeries($groupByField, $data);

        if ($chartType === 'all') {
            $result = [];

            // If no chart config found, default to a sane set of types
            $types = array_keys($this->chart);
            if (empty($types)) {
                $types = ['column', 'bar', 'line', 'spline', 'area', 'pie'];
            }

            foreach ($types as $type) {
                if ($type === 'pie') {
                    [$categories, $series] = $this->formatPieSeries($groupByField, $data);
                }

                $result[$type] = $this->prepareChart($type, $categories, $series);
            }
            return $result;
        }

        return $this->prepareChart($chartType ?? 'column', $categories, $series);
    }

    /**
     * Helper to format categories and series for chart data.
     *
     * @param string $groupByField The field to group data by.
     * @param array $data The input data.
     * @return array               The formatted categories and series.
     */
    private function formatStanderSeries(string $groupByField, array $data): array
    {
        $series = $categories = [];
        foreach ($data as $item) {
            foreach ((array)$item as $k => $v) {
                if ($k === $groupByField) {
                    $categories[] = rb_resolve_trans($v, 'report');
                } elseif (!Str::endsWith($k, '_id')) {
                    $series[$k]['name'] = rb_resolve_trans($k, 'report');
                    $series[$k]['data'][] = $v;
                }
            }
        }

        return [$categories, $series];
    }

    /**
     * @param string $groupByField
     * @param array $data
     * @return array[]
     */
    private function formatPieSeries(string $groupByField, array $data): array
    {
        $transformed = [];
        foreach ($data as $item) {
            $item = (array)$item;
            $preparedKey = $item[$groupByField];
            $keys = array_keys($item);
            unset($keys[array_search($groupByField, $keys, false)]);
            unset($item[$groupByField]);

            // Ensure $item contain numeric
            $list = array_map(fn($i) => is_numeric($i) ? $i : 0, \Arr::wrap($item));

            $transformed[$preparedKey] = array_sum($list);
        }

        $series = $categories = [];
        foreach ($transformed as $k => $v) {
            if (!Str::endsWith($k, '_id')) {
                $series[$k]['name'] = rb_resolve_trans($k, 'report');
                $series[$k]['data'][] = $v;
            }
        }

        return [$categories, $series];
    }

    /**
     * Prepare the appropriate chart data based on the chart type.
     *
     * @param string $chartType The type of chart ('bar', 'line', 'pie', etc.).
     * @param array $categories The categories for the chart.
     * @param array $series The series data for the chart.
     * @return array              The prepared chart data.
     */
    private function prepareChart(string $chartType, array $categories, array $series): array
    {
        $chartMapping = [
            'column' => 'column',
            'bar' => 'bar',
            'line' => 'line',
            'spline' => 'spline',
            'area' => 'area',
            'pie' => 'preparePieChart',
        ];

        // For pie chart, call its specific method
        if ($chartType === 'pie') {
            return $this->preparePieChart($categories, $series);
        }

        // For bar, column, line, spline, area, use the shared method
        return $this->prepareStandardChart($categories, $series, $chartMapping[$chartType] ?? 'column');
    }

    /**
     * Common chart preparation method for standard chart types (bar, column, line, area, spline).
     *
     * @param array $categories The categories for the chart.
     * @param array $series The series data for the chart.
     * @param string $chartType The type of chart ('bar', 'line', 'area', etc.).
     * @return array            The prepared chart data.
     */
    private function prepareStandardChart(array $categories, array $series, string $chartType): array
    {
        $chart = $this->chart[$chartType];
        $chart['xAxis']['categories'] = $categories;
        $chart['series'] = collect(array_values($series))->map(function ($item) {
            $item['color'] = 'rgba(var(--v-theme-primary),1)';
            return $item;
        })->toArray();

        return $chart;
    }

    /**
     * Prepare data for a pie chart.
     *
     * @param array $categories
     * @param array $series
     * @return array        The prepared chart data.
     */
    private function preparePieChart(array $categories, array $series): array
    {
        $chart = $this->chart['pie'];
        $chart['series'][] = [
            'name' => rb_resolve_trans('count', 'report'),
            'colorByPoint' => true,
            'data' => collect($series)
                ->map(fn($item) => [
                    'name' => rb_resolve_trans($item['name'], 'report'),
                    'y' => array_sum($item['data'])
                ])
                ->values()
                ->toArray(),
        ];

        return $chart;
    }
}
