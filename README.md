# 📊 Laravel Report Builder

[![Latest Version on Packagist](https://img.shields.io/packagist/v/hasanhawary/report-builder.svg?style=flat-square)](https://packagist.org/packages/hasanhawary/report-builder)
[![Total Downloads](https://img.shields.io/packagist/dt/hasanhawary/report-builder.svg?style=flat-square)](https://packagist.org/packages/hasanhawary/report-builder)
[![License](https://img.shields.io/github/license/hasanhawary/report-builder.svg?style=flat-square)](LICENSE.md)

A powerful, modular **report builder for Laravel** that simplifies the generation of **cards, charts, and tables**.  
Easily configurable via `config/report.php`, it provides a unified JSON structure perfect for **HighCharts** and modern frontends like Vue, React, or Inertia.

---

## ✨ Features

- 🚀 **Unified Data Structure**: Seamlessly generate cards, charts, and tables.
- 📈 **HighCharts Ready**: Pre-configured for popular chart types (Bar, Line, Spline, Area, Column, Pie).
- 🧩 **Modular & Clean**: Define report pages in config and implement logic in dedicated classes.
- 🛠 **Dynamic Filtering**: Built-in support for date ranges and custom advanced filters.
- 🌍 **Multi-language Support**: Automatic translation of labels and titles using Laravel's localization.
- 🎨 **Frontend Agnostic**: Clean JSON response ready for any JS library.

---

## 📦 Installation

```bash
composer require hasanhawary/report-builder
```

Publish the configuration files:

```bash
php artisan vendor:publish --tag=report-builder-config
```

The package registers its own routes automatically. By default:

- `GET /api/report` (`report.index`)

You can disable, move, or rename this route from `config/report.php` if the host project
already uses the same URLs or route names.

### Upgrade Note

If you previously published `config/report.php`, Composer updates will not overwrite that file.
To use the current default package route `GET /api/report`, update your published config manually
or republish it:

```bash
php artisan vendor:publish --tag=report-builder-config --force
```

---

## ⚙️ Configuration

Define your report structure in **`config/report.php`**:

```php
return [
    'routes' => [
        'enabled' => true,
        'prefix' => 'api/report',
        'middleware' => ['api'],
        'name_prefix' => 'report.',
        'paths' => [
            'report' => '/',
        ],
        'names' => [
            'report' => 'index',
        ],
    ],

    'namespace' => 'App\\Reports', // Optional global namespace for report classes.

    'translate' => [
        'enabled' => true,
        'trans_file' => 'report',
    ],

    'pages' => [
        'user' => [
            'type' => 'page',
            // Optional explicit class. This is recommended when you do not use a global namespace.
            'class' => App\Reports\UserReport::class,
            'report' => [
                'stats' => [
                    'type' => 'card',
                    'size' => ['cols' => '12', 'md' => '12', 'lg' => '12'],
                ],
                'registered_by_date' => [
                    'type' => 'spline',
                    'size' => ['cols' => '12', 'md' => '6', 'lg' => '6'],
                ],
                'user_list' => [
                    'type' => 'table',
                    'size' => ['cols' => '12', 'md' => '6', 'lg' => '6'],
                ],
            ],
        ]
    ],
];
```

---

## 🛣 Prepared Routes

Report Builder ships its Laravel routes from the package service provider, so host projects do
not need to copy routes or controllers into `routes/web.php`, `routes/api.php`, or `app/Http`.

Default prepared routes:

| Method | URI | Route name | Controller |
| --- | --- | --- | --- |
| `GET` | `/api/report` | `report.index` | `HasanHawary\ReportBuilder\Http\Controllers\ReportController` |

The default middleware is `['api']`.

For protected report APIs, publish the config and add your auth middleware:

```php
'routes' => [
    'enabled' => true,
    'prefix' => 'api/report',
    'middleware' => ['api', 'auth:sanctum'],
    'name_prefix' => 'report.',
],
```

### Avoiding Route Conflicts

If your Laravel project already has `/api/report` or `report.index`, publish the
config and change the package route settings:

```bash
php artisan vendor:publish --tag=report-builder-config
```

Move the package route under another URL prefix:

```php
'routes' => [
    'enabled' => true,
    'prefix' => 'api/report-builder',
    'middleware' => ['api'],
    'name_prefix' => 'report-builder.',
],
```

This changes the default routes to:

- `GET /api/report-builder` (`report-builder.index`)

You can also change only the individual paths and names:

```php
'routes' => [
    'enabled' => true,
    'prefix' => 'api',
    'middleware' => ['api'],
    'name_prefix' => 'rb.',
    'paths' => [
        'report' => 'report-builder',
    ],
    'names' => [
        'report' => 'index',
    ],
],
```

Or disable the package routes completely if you only want to instantiate `ReportBuilder`
directly:

```php
'routes' => [
    'enabled' => false,
],
```

Route configuration is merged with package defaults, so a host project can override only the
values it needs. Setting `middleware` to an empty array is supported when you need public or
API-only routes:

```php
'middleware' => [],
```

---

## 🛠 Usage

### 1. Create your Report Class

Extend `BaseReport` and implement methods matching your config keys (prefixed with `get`).

```php
namespace App\Reports;

use HasanHawary\ReportBuilder\BaseReport;
use Illuminate\Support\Facades\DB;

class UserReport extends BaseReport
{
    public string $table = 'users';

    /**
     * Data for summary cards
     */
    public function getStats(): array
    {
        $data = [
            'total' => DB::table($this->table)->count(),
            'active' => DB::table($this->table)->where('active', 1)->count(),
        ];

        return $this->cardResponse($data);
    }

    /**
     * Data for a spline chart
     */
    public function getRegisteredByDate(): array
    {
        $data = DB::table($this->table)
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m-%d') as date, COUNT(*) as count")
            ->groupBy('date')
            ->get();

        return $this->chartResponse('date', $data);
    }

    /**
     * Data for a table
     */
    public function getUserList(): array
    {
        $data = DB::table($this->table)
            ->select('id', 'name', 'email', 'created_at')
            ->limit(10)
            ->get();

        return $this->chartResponse('id', $data); // chartResponse handles 'table' type automatically
    }
}
```

### 2. Fetch the Report

Use the package route or instantiate `ReportBuilder` directly.

```php
use HasanHawary\ReportBuilder\ReportBuilder;

public function index()
{
    return (new ReportBuilder([
        'page' => 'user',
        'start' => '2024-01-01',
        'end' => '2024-12-31',
    ]))->response();
}
```

---

## 📊 Available Response Helpers

Inside your report classes, use these helpers to format data:

- `$this->cardResponse($data)`: Formats simple key-value pairs into card data.
- `$this->chartResponse($groupByField, $data)`: Formats collections into HighCharts-ready structures.
- `$this->guessDateFormat()`: Automatically determines the best date format for chart axes based on filtered range.

---

## 🌍 Localization

The package reads report titles, card labels, chart categories, chart series names, and table
columns from the configured translation file:

```php
'translate' => [
    'enabled' => true,
    'trans_file' => 'report',
],
```

With the default value, label keys like `total_users` are resolved from:

```php
__('report.total_users')
```

To use another file, set `trans_file`:

```php
'translate' => [
    'trans_file' => 'dashboard',
],
```

Then the same key is resolved from:

```php
__('dashboard.total_users')
```

---

## 🎨 Frontend Integration

### JSON Response

Calling `GET /api/report?page=user` returns a JSON envelope. Frontend chart and card data lives
under `data.report`.

```json
{
  "status": true,
  "code": 200,
  "message": "Success",
  "data": {
    "report": {
      "cards": {
        "data": [
          {
            "key": "total_users",
            "label": "Total users",
            "value": 120
          }
        ]
      },
      "charts": [
        {
          "type": "spline",
          "title": "registered_by_date",
          "data": {
            "spline": {}
          }
        }
      ],
      "tables": []
    }
  }
}
```

### Vue 3 + Highcharts

```vue
<script setup>
import { ref, onMounted } from 'vue';
import Highcharts from 'highcharts';

const reports = ref(null);

onMounted(async () => {
  const res = await fetch('/api/report?page=user');
  const json = await res.json();
  reports.value = json.data.report;
});
</script>

<template>
  <div v-if="reports">
    <!-- Render Cards -->
    <div v-for="card in reports.cards.data" :key="card.key">
       {{ card.label }}: {{ card.value }}
    </div>

    <!-- Render Charts -->
    <div v-for="chart in reports.charts" :key="chart.title">
       <highcharts :options="chart.data[chart.type]" />
    </div>
  </div>
</template>
```

---

## ✅ Version Support

- **PHP**: 8.0+
- **Laravel**: 10, 11, 12, 13

## Runtime Boundary

This package is designed for Laravel applications. The route, controller, request validation,
translation, database helpers, and service provider are Laravel-only features and require a
Laravel application container.

The package does not depend on any `App\...` class by default. Host applications provide report
classes either with `report.namespace` or with a page-specific `report.pages.{page}.class`.
If no report class is configured, the package returns a clear configuration error instead of
assuming an application namespace.

Plain PHP projects can reuse simple data-shaping ideas from the package, but the shipped
implementation expects Laravel helpers such as `config()`, `app()`, `trans()`, and Laravel's
database/query components. In non-Laravel runtimes, use this package behind a Laravel adapter
or provide your own wrapper around the report classes.

---

## 📜 License

MIT © [Hasan Hawary](https://github.com/hasanhawary)
