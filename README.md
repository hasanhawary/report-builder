# Report Builder

[![Latest Stable Version](https://img.shields.io/packagist/v/hasanhawary/report-builder.svg)](https://packagist.org/packages/hasanhawary/report-builder)
[![Total Downloads](https://img.shields.io/packagist/dm/hasanhawary/report-builder.svg)](https://packagist.org/packages/hasanhawary/report-builder)
[![PHP Version](https://img.shields.io/packagist/php-v/hasanhawary/report-builder.svg)](https://packagist.org/packages/hasanhawary/report-builder)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

Composable, framework-friendly report generation for Laravel.  
Define simple report classes that return cards, charts, and tables, with built-in support for filters, mixed pages, and chart type handling.

---

## 🚀 Features

- Simple report classes with a consistent API (`report()`)
- Cards, charts, and tables in a single response structure
- Mixed pages: aggregate multiple report parts from different pages
- Flexible filtering (date range, types, advanced filters)
- Auto namespace discovery via config
- Safe fallbacks and error handling

---

## 📦 Installation

```bash
composer require hasanhawary/report-builder
```

The package auto-discovers its service provider. No manual registration required.

Optionally publish the default configuration to config/report.php and config/chart.php:

```bash
php artisan vendor:publish --tag=report-builder-config
```

---

## ⚡ Quick Start

1) Configure your report namespace in `config/report.php` (published by your app):

```php
return [
    'namespace' => 'App\\Reports',

    'pages' => [
        'sales' => [
            'type' => 'page',
            'report' => [
                'summary' => ['type' => 'card'],
                'monthly' => ['type' => 'chart'],
                'top_customers' => ['type' => 'table'],
            ],
        ],
    ],
];
```

2) Create a report class under your namespace. The class name should be `{Page}ReportBuilder` (StudlyCase):

```php
namespace App\Reports;

use HasanHawary\ReportGenerator\Types\BaseReport;

class SalesReportBuilder extends BaseReport
{
    public string $table = 'orders';

    // Each function name: get{Type}(), where {Type} matches keys in config (e.g., summary, monthly, top_customers)
    public function getSummary(): array
    {
        return [
            'type' => 'card',
            'items' => [
                ['title' => 'Total Orders', 'value' => 120],
                ['title' => 'Revenue', 'value' => 55000],
            ],
        ];
    }

    public function getMonthly(): array
    {
        return [
            'type' => 'chart',
            'chart' => 'bar',
            'labels' => ['Jan', 'Feb', 'Mar'],
            'datasets' => [
                ['label' => 'Orders', 'data' => [30, 40, 50]],
            ],
        ];
    }

    public function getTopCustomers(): array
    {
        return [
            'type' => 'table',
            'columns' => ['Customer', 'Orders', 'Amount'],
            'rows' => [
                ['Alice', 10, 1200],
                ['Bob', 9, 1100],
            ],
        ];
    }
}
```

3) Generate a response:

```php
use HasanHawary\ReportGenerator\ReportBuilder;

// Typical filter from request
$filter = [
    'page' => 'sales',
    'types' => 'all', // or 'summary,monthly' or ['summary','monthly']
    'apply_date' => true,
    'start' => '2025-01-01',
    'end' => '2025-01-31',
];

$response = (new ReportBuilder($filter))->response();
```

Response structure example:

```json
{
  "report": {
    "title": "Sales ReportBuilder",
    "page": "sales",
    "cards": [],
    "charts": [],
    "tables": []
  },
  "filter": { "page": "sales" }
}
```

---

## 🧠 Writing Your Query (Important)

In each get{Type}() method of your {Page}ReportBuilder class, you are responsible for writing the query and shaping the data. The package does not generate SQL for you. Typical flow:

- Use Eloquent/Query Builder to fetch and aggregate data.
- Apply the built-in date and advanced filters if needed.
- Return one of: a card structure, a chart-ready structure, or a table array.

Example with date filters and soft-delete checks:

```php
public function getMonthly(): array
{
    $q = DB::table($this->table)
        ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as orders')
        ->groupBy('month');

    $this->applyDateFilter($q);     // honors filter[apply_date], filter[start], filter[end]
    $this->checkSoftDelete($q);     // skip soft-deleted rows if deleted_at exists

    $rows = $q->get();

    // Prepare for charts (you can also use ChartHandler directly)
    $chartData = (new \HasanHawary\ReportGenerator\Types\ChartHandler($this->filter['prefer_chart'] ?? null))
        ->resolve('month', $rows, $this->filter['prefer_chart'] ?? 'all');

    return [
        'type' => 'chart',
        'chart' => $this->filter['prefer_chart'] ?? 'all',
        'data' => $chartData,
    ];
}
```

## 🎛️ Filters You Can Apply

The filter array typically contains:
- page: which report page to run (required)
- types: which parts to include (e.g., "all", "summary,monthly", or ["summary","monthly"])  
- apply_date: boolean to enable date range
- start, end: Y-m-d dates used when apply_date = true
- advanced: optional list of extra filters you can apply in your queries
- prefer_chart: optional chart type preference (bar, line, column, area, spline, pie, table, all)

Inside your get* methods, call:
- applyDateFilter($query, $dateColumn = null, $table = null)
- checkSoftDelete($query, $table = null)

## 📊 Chart Handler (All Types, Filters-Friendly)

ChartHandler transforms your aggregated rows into chart-ready arrays:
- resolve(groupByField, data, chartType = 'all')
- chartType can be: bar, line, column, area, spline, pie, table, all
- 'table' returns the data unchanged for grid renderers
- 'all' returns an array keyed by each supported chart type
- When chart config is missing, it safely falls back to sensible defaults

You can pass Illuminate collections or plain arrays.

## 🧷 Cards and Tables

- Cards: return a structure like:
  - { type: 'card', items: [ { title, value, trend? }, ... ] }
- Tables: return a structure like:
  - { type: 'table', columns: [...], rows: [...] }

These are grouped automatically in the final response under report.cards and report.tables.

## 🧭 Package Capabilities (At a Glance)

- Page-based reports with class auto-resolution by namespace
- Mixed pages that merge parts from multiple reports
- Cards, charts, and tables in one consistent response
- Date and advanced filters helpers you can apply in your queries
- Chart handling for many types, including an 'all' bundle response
- Safe fallbacks and error handling to avoid breaking mixed pages
- Simple extension points: add new get* methods and config keys

## 🔀 Mixed Reports

Combine pieces from multiple pages into one mixed result. In `config/report.php`:

```php
return [
    'namespace' => 'App\\Reports',

    'pages' => [
        'sales' => [ 'type' => 'page', 'report' => ['summary' => ['type' => 'card']] ],
        'traffic' => [ 'type' => 'page', 'report' => ['visits' => ['type' => 'chart']] ],

        'dashboard' => [
            'type' => 'mixed',
            'report' => [
                'sales.summary',
                'traffic.visits' => ['chart' => 'line'], // override example
            ],
        ],
    ],
];
```

Use it:

```php
$filter = [ 'page' => 'dashboard', 'types' => 'all' ];
$response = (new ReportBuilder($filter))->response();
```

---

## 🧩 BaseReport Helpers

- Date filter helpers: `applyDateFilter($query, $column = null, $table = null)`
- Soft-delete guard: `checkSoftDelete($query, $table = null)`
- Smart x-axis format: `guessDateFormat($table = null, $date = null)`
- Types resolver: `resolveTypes($types = null)` accepts `all`, comma-separated string, or array
- ResponsesTrait helpers to format output consistently

Your `BaseReport` subclass can use `$this->filter` which includes:
- page: report page key
- types: the parts you want to render (cards/charts/tables defined in config)
- apply_date, start, end: optional date range
- prefer_chart: optional preferred chart type for chart rendering
- mixed_page: for mixed pages, internal mapping of overrides

---

## ⚙️ Configuration Reference

Application config at `config/report.php` (your app should provide this):

```php
return [
    'namespace' => 'App\\Reports',

    'pages' => [
        // A normal page: class must be App\Reports\{Page}ReportBuilder
        'sales' => [
            'type' => 'page',
            'report' => [
                'summary' => ['type' => 'card'],
                'monthly' => ['type' => 'chart'],
                'top_customers' => ['type' => 'table'],
            ],
        ],

        // A mixed page composed of parts from other pages
        'dashboard' => [
            'type' => 'mixed',
            'report' => [
                'sales.summary',
                'sales.monthly',
            ],
        ],
    ],
];
```

---

## 🌐 Controller Example

```php
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use HasanHawary\ReportGenerator\ReportBuilder;

Route::get('/reports', function (Request $request) {
    $filter = $request->validate([
        'page' => 'required|string',
        'types' => 'nullable',
        'apply_date' => 'boolean',
        'start' => 'nullable|date',
        'end' => 'nullable|date',
    ]);

    return response()->json((new ReportBuilder($filter))->response());
});
```

---

## ✅ Version Support

- PHP: 8.0 – 8.5
- Laravel: 8 – 12

---

## 📜 License

MIT © [Hasan Hawary](https://github.com/hasanhawary)
