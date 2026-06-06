<?php

namespace HasanHawary\ReportBuilder;

use Carbon\Carbon;
use BadMethodCallException;
use HasanHawary\ReportBuilder\Exceptions\ReportComponentException;
use HasanHawary\ReportBuilder\Trait\ResponsesTrait;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

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
    protected ?string $currentComponentType = null;
    protected static array $softDeleteColumns = [];

    /**
     */
    public function __construct(public array $filter)
    {
        $this->disableMysqlStrictMode();

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
            $component = $this->resolveComponent((string) $type);

            if ($component === null) {
                return;
            }

            match ($component['type']) {
                'card' => $report['cards'] = $component['data'],
                'table' => $report['tables'][] = $component['data'],
                default => $report['charts'][] = $component['data']
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

        $wrappedDate = DB::connection()->getQueryGrammar()->wrap($date);

        $range = DB::table($table)
            ->where(fn($q) => $this->applyDateFilter($q, $date, $table))
            ->selectRaw("MIN({$wrappedDate}) as start_date, MAX({$wrappedDate}) as end_date")
            ->first();

        $start = $range?->start_date;
        $end = $range?->end_date;

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

            if(isset($this->filter['start']) && $this->filter['start']) {
                $q->whereDate($column, '>=', $this->filter['start']);
            }

            if(isset($this->filter['end']) && $this->filter['end']) {
                $q->whereDate($column, '<=', $this->filter['end']);
            }
        });
    }

    /**
     * Check for soft deletes in the table.
     */
    public function checkSoftDelete($query, ?string $table = null): void
    {
        $table = $table ?? $this->table;
        $query->when($this->hasSoftDeleteColumn($table), fn($q) => $q->whereNull("$table.deleted_at"));
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

    protected function resolveComponent(string $type): ?array
    {
        $componentType = $this->mixedCharts[$type]['type'] ?? $this->charts[$type]['type'] ?? null;
        $method = $this->componentMethod($type);
        $previousComponentType = $this->currentComponentType;

        try {
            if (!method_exists($this, $method)) {
                throw new BadMethodCallException("Report component method [{$method}] is missing.");
            }

            $this->currentComponentType = $type;

            return [
                'type' => $componentType,
                'data' => $this->{$method}(),
            ];
        } catch (Throwable $e) {
            return $this->handleComponentException($type, $e);
        } finally {
            $this->currentComponentType = $previousComponentType;
        }
    }

    protected function applyAdvancedFilters($q): void
    {
        if (!empty($this->filter['advanced'])) {
            collect($this->filter['advanced'])->each(function ($filter) use ($q) {
                $key = data_get($filter, 'key');

                if (!is_string($key) || trim($key) === '') {
                    return;
                }

                $q->whereIn($key, Arr::wrap(data_get($filter, 'value')));
            });
        }
    }

    private function componentMethod(string $type): string
    {
        return 'get' . ucfirst(Str::camel($type));
    }

    private function handleComponentException(string $type, Throwable $e): ?array
    {
        if ($this->componentErrorMode() === 'omit') {
            return null;
        }

        throw new ReportComponentException("Report component [{$type}] failed: {$e->getMessage()}", (int) $e->getCode(), $e);
    }

    private function componentErrorMode(): string
    {
        $mode = config('report.component_errors', 'throw');

        return $mode === 'omit' ? 'omit' : 'throw';
    }

    private function disableMysqlStrictMode(): void
    {
        if (!config('report.database.disable_mysql_strict_mode', true)) {
            return;
        }

        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('SET sql_mode = " "');
    }

    private function hasSoftDeleteColumn(string $table): bool
    {
        $key = DB::connection()->getName() . ':' . $table;

        return self::$softDeleteColumns[$key] ??= Schema::hasColumn($table, 'deleted_at');
    }
}
