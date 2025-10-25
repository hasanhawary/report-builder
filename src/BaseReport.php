<?php

namespace HasanHawary\ReportBuilder;

use Carbon\Carbon;
use Exception;
use HasanHawary\ReportBuilder\Trait\ResponsesTrait;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

abstract class BaseReport
{
    use ResponsesTrait;

    public const DEFAULT_TITLE = 'report';
    public string $title;
    public array $charts;
    public array $mixedCharts;
    public string $dateColumn;
    public string $lang;
    public ChartHandler $chartHandler;

    /**
     */
    public function __construct(public array $filter)
    {
        try {
            DB::statement('SET sql_mode = " "'); // Disable strict mode (optional)
        } catch (\Throwable $e) {
            // DB facade may not be available in non-Laravel environments
        }

        // Load the report configuration
        $config = $this->loadReportConfig($filter['page']);

        $this->dateColumn = $filter['dateColumn'] ?? 'created_at';
        $this->lang = app()->getLocale() ?? 'en';
        $this->chartHandler = new ChartHandler($filter['prefer_chart'] ?? null);
        $this->charts = $config['report'] ?? [];
        $this->mixedCharts = $this->filter['mixed_page'] ?? $config['report'] ?? [];
        $this->title = rb_resolve_trans("{$filter['page']}_report");
    }

    /**
     * Checks if the report functionality is enabled.
     */
    public function isEnabled(): bool
    {
        return true;
    }

    /**
     * Generate the report
     */
    public function report(): array
    {
        $report = [
            'title' => $this->title ?? self::DEFAULT_TITLE,
            'page' => $this->filter['page']
        ];

        $this->resolveTypes($this->filter['types'] ?? null)->each(function ($type) use (&$report) {
            $chart_type = $this->mixedCharts[$type]['type'] ?? $this->charts[$type]['type'] ?? null;
            $chart = [];

            try { // Prepare the name of the report function based on the type , then call it
                $chart = $this->{'get' . ucfirst(Str::camel($type))}();
            } catch (Exception|\Error $e) {
                // Optional: log error if your app has a logger
            }

            match ($chart_type) {
                'card' => $report['cards'] = $chart,
                'table' => $report['tables'][] = $chart,
                default => $report['charts'][] = $chart
            };
        });

        return ['report' => $report, 'filter' => $this->filter];
    }

    /**
     * Guess the correct date format based on the duration.
     */
    public function guessDateFormat($table = null, $date = null): string
    {
        $table = $table ?? $this->table;
        $date = $date ?? $this->dateColumn;

        $start = DB::table($table)
            ->where(fn($q) => $this->applyDateFilter($q, $date, $table))
            ->min($date);

        $end = DB::table($table)
            ->where(fn($q) => $this->applyDateFilter($q, $date, $table))
            ->max($date);

        if (!$start || !$end) {
            return "%b-%Y";
        }

        // Calculate the difference in days
        $diff = Carbon::parse($start)->startOfDay()->diffInDays(Carbon::parse($end)->endOfDay());
        $diff = $diff ?: 'today';

        return match (true) {
            $diff === 'today' => '%h:00 %p',
            $diff < 32 && $diff >= 1 => '%d-%b-%Y',
            $diff < 367 && $diff >= 32 => '%b-%Y',
            $diff >= 367 => '%Y',
            default => "%b-%Y",
        };
    }

    /**
     * Apply date filters for the query.
     */
    public function applyDateFilter($query, ?string $column = null, ?string $table = null): void
    {
        $column = ($table ?? $this->table) . '.' . ($column ?? $this->dateColumn);
        $query->when(isset($this->filter['apply_date']) && $this->filter['apply_date'], function ($q) use ($column) {
            $q->whereDate($column, '>=', $this->filter['start'] ?? null)
                ->whereDate($column, '<=', $this->filter['end'] ?? null);
        });
    }

    /**
     * Check for soft deletes in the table.
     */
    public function checkSoftDelete($query, ?string $table = null): void
    {
        $table = $table ?? $this->table;
        $query->when(Schema::hasColumn($table, 'deleted_at'), fn($q) => $q->whereNull("$table.deleted_at"));
    }

    /**
     * Resolve missing values in the data list.
     */
    protected function resolveMissedValues($data, $list): array
    {
        $list = array_keys($list);
        foreach ($data as &$item) {
            $key = array_keys($item);
            $diff = array_values(array_diff($list, $key));
            foreach ($diff as $value) {
                $item[$value] = 0;
            }
        }

        return $data;
    }

    /**
     * Resolve the types of charts to display.
     */
    protected function resolveTypes($types = null): Collection
    {
        if (is_array($types)) {
            return collect($types);
        }

        if (is_string($types) && $types !== 'all') {
            return collect(explode(',', $types));
        }

        return collect(array_keys($this->charts));
    }

    /**
     * Load the report configuration from the JSON file.
     *
     */
    protected function loadReportConfig($page): array
    {
        return config("report.pages.$page");
    }

    private function applyAdvancedFilters($q): void
    {
        if (!empty($this->filter['advanced'])) {
            collect($this->filter['advanced'])->each(function ($filter) use ($q) {
                try {
                    $q->whereIn($filter->key, Arr::wrap($filter->value));
                } catch (\Exception $e) {
                    // Optional: log error
                }
            });
        }
    }
}
