<?php

namespace HasanHawary\ReportBuilder;

use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class ReportBuilder
{
    protected array $config = [];
    protected ?string $namespace = null;

    public function __construct(public array $filter = [])
    {
        // Initialize namespace from config at runtime
        $this->namespace = config('report.namespace') ?? __DIR__;
    }

    /**
     * Main entry point to generate the report.
     *
     * @return mixed
     */
    public function response(): mixed
    {
        // Validate required filter keys
        if (!isset($this->filter['page']) || !is_string($this->filter['page']) || $this->filter['page'] === '') {
            throw new \InvalidArgumentException('Missing required filter key: page');
        }

        if (!$this->config = $this->loadReportConfig($this->filter['page'])) {
            throw new RuntimeException('ReportBuilder page not found');
        }

        return ($this->config['type'] ?? null) === 'mixed'
            ? $this->handleMixedReport($this->config)
            : $this->handlePageReport($this->filter['page']);
    }

    /**
     * Resolve and execute the report class.
     *
     * @param string $path
     * @param bool $mixed
     * @param array|null $filterOverride
     * @return mixed
     */
    public function resolve(string $path, bool $mixed = false, ?array $filterOverride = null): mixed
    {
        try {
            if (!class_exists($path)) {
                throw new RuntimeException("ReportBuilder class not found: {$path}");
            }

            $filter = $filterOverride ?? $this->filter;
            $object = new $path($filter);

            if (method_exists($object, 'isEnabled') && !$object->isEnabled()) {
                if ($mixed) {
                    return [];
                }

                abort(403);
            }

            return method_exists($object, 'report') ? $object->report() : [];

        } catch (Throwable $e) {
            return $mixed ? [] : throw new RuntimeException($e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    protected function handlePageReport(string $page): mixed
    {
        return $this->resolve($this->buildReportPath($page));
    }

    protected function handleMixedReport(array $pageConfig): array
    {
        $reports = [];
        [$pages, $mixedPage] = $this->prepareMixedReport($pageConfig);
        $result['filter'] = $this->filter;

        foreach ($pages as $reportPage => $types) {
            $filter = $this->filter; // non-mutating copy
            $filter['types'] = $types;
            $filter['page'] = $reportPage;
            $filter['mixed_page'] = $mixedPage;

            $path = $this->buildReportPath($reportPage);
            $resolved = $this->resolve($path, true, $filter);
            $reports[] = is_array($resolved) ? ($resolved['report'] ?? []) : [];
        }

        return $this->mergeReports($reports, $result);
    }

    /**
     * Prepare the mapping of mixed report pages and the per-type mixed configuration.
     *
     * @return array{0: array<string, array<int,string>>, 1: array<string, mixed>|null}
     */
    protected function prepareMixedReport(array $pageConfig): array
    {
        $pages = [];
        $mixedPage = [];

        foreach (($pageConfig['report'] ?? []) as $key => $value) {
            $title = is_numeric($key) ? $value : $key;
            $pageName = explode('.', $title)[0];
            $titleKey = str_replace("$pageName.", '', $title);

            $pages[$pageName][] = $titleKey;
            $mixedPage[$titleKey] = is_array($value) && !empty($value) ? $value : null;
        }

        return [$pages, $mixedPage ?: null];
    }

    protected function mergeReports(array $reports, array $result): array
    {
        foreach ($reports as $report) {
            foreach (['cards', 'charts', 'tables'] as $attribute) {
                if (!empty($report[$attribute])) {
                    $result['report'][$attribute] = array_merge($result['report'][$attribute] ?? [], $report[$attribute]);
                }
            }
        }

        return $result;
    }

    /**
     * Build the fully qualified class name for a page's report by resolving
     * namespaces from config (per-page first, then global). No filter-based
     * namespace is used. Falls back to package default when none provided.
     */
    protected function buildReportPath(string $page): ?string
    {
        $base = ucfirst(Str::camel($page));
        $candidates = [
            $this->namespace . '\\' . $base . 'ReportBuilder',
            $this->namespace . '\\' . $base . 'Report',
        ];

        foreach ($candidates as $fqcn) {
            if (class_exists($fqcn)) {
                return $fqcn;
            }
        }

        // Return the first candidate by default (will fail later with a clear error)
        return $candidates[0];
    }

    /**
     * Load report configuration from a PHP file returning an array.
     * Prefer application's config/report.php; fallback to the package default.
     */
    protected function loadReportConfig($page): array
    {
        return config("report.pages.$page");
    }
}
