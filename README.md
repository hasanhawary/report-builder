# Laravel Report Builder

[![Latest Version on Packagist](https://img.shields.io/packagist/v/hasanhawary/report-builder.svg?style=flat-square)](https://packagist.org/packages/hasanhawary/report-builder)
[![Total Downloads](https://img.shields.io/packagist/dt/hasanhawary/report-builder.svg?style=flat-square)](https://packagist.org/packages/hasanhawary/report-builder)
[![License](https://img.shields.io/github/license/hasanhawary/report-builder.svg?style=flat-square)](LICENSE.md)

A flexible **report builder for Laravel** that helps you generate **cards, charts, and tables** from your data models with minimal effort.  
Easily configurable via `config/report.php`, with support for **HighCharts**, **dynamic filters**, and **multiple report pages**.

---

## 🚀 Features

- 🔹 Generate **cards**, **charts**, and **tables** with unified structure.  
- 🔹 Out-of-the-box support for **HighCharts**.  
- 🔹 Simple configuration using `config/report.php`.  
- 🔹 Build multiple report pages (`users`, `orders`, etc.).  
- 🔹 Extendable via your own Report classes.  
- 🔹 JSON response format ready for any frontend (Vue, React, Inertia, Livewire...).  

---

## 📦 Installation

```bash
composer require hasanhawary/report-builder
````

Optionally publish config file:

```bash
php artisan vendor:publish --tag=report-config
```

---

## ⚙️ Configuration

Your reports are defined in **`config/report.php`**:

```php
return [
    'namespace' => 'App\\Reports',

    'pages' => [
        'users' => [
            'report' => [
                'by_status' => ['type' => 'chart'],
                'summary'   => ['type' => 'card'],
            ],
        ],
    ],
];
```

* `namespace` → location of your custom report classes.
* `pages` → defines available reports per page.

---

## 🛠 Usage

### Create a Report

Create `App/Reports/UserReport.php`:

```php
namespace App\Reports;

use HasanHawary\ReportBuilder\BaseReport;
use Illuminate\Support\Facades\DB;

class UserReport extends BaseReport
{
    public string $table = 'users';

    public function getByStatus(): array
    {
        $data = DB::table($this->table)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get();

        return $this->chartResponse('status', $data, 'pie');
    }

    public function getSummary(): array
    {
        $data = [
            ['name' => 'Total Users', 'value' => DB::table($this->table)->count()],
            ['name' => 'Active Users', 'value' => DB::table($this->table)->where('status', 'active')->count()],
        ];

        return $this->cardResponse($data);
    }
}
```

---

### Call from Controller or Route

```php
use HasanHawary\ReportBuilder\ReportBuilder;

Route::get('/reports/users', function () {
    return (new ReportBuilder([
        'page' => 'users',
        'types' => 'all',
        'prefer_chart' => 'bar',
    ]))->response();
});
```

---

### Example Response

```json
{
  "report": {
    "title": "users_report",
    "page": "users",
    "cards": {
      "type": "card",
      "size": {
        "cols": "6",
        "md": "3",
        "lg": "3"
      },
      "title": "cards",
      "data": [
        {
          "key": "total_users",
          "value": 200,
          "label": "Total Users",
          "size": {
            "cols": "6",
            "md": "3",
            "lg": "3"
          }
        },
        {
          "key": "active_users",
          "value": 150,
          "label": "Active Users",
          "size": {
            "cols": "6",
            "md": "3",
            "lg": "3"
          }
        }
      ]
    },
    "tables": [
      {
        "type": "table",
        "size": {
          "cols": "12",
          "md": "12",
          "lg": "12"
        },
        "title": "Users by Status",
        "data": [
          {
            "status": "active",
            "count": 10
          },
          {
            "status": "inactive",
            "count": 5
          }
        ],
        "columns": [
          {
            "title": "Status",
            "key": "status"
          },
          {
            "title": "Count",
            "key": "count"
          }
        ]
      }
    ],
    "charts": [
      {
        "type": "bar",
        "size": {
          "cols": "12",
          "md": "6",
          "lg": "6"
        },
        "title": "Users Activity",
        "data": {
          "bar": {
            "chart": {
              "type": "bar",
              "style": {
                "fontFamily": "Cairo , Poppins, sans-serif"
              }
            },
            "xAxis": {
              "categories": ["active", "inactive"]
            },
            "yAxis": {
              "title": {
                "text": "Users"
              }
            },
            "series": [
              {
                "name": "count",
                "data": [10, 5],
                "color": "rgba(var(--v-theme-primary),1)"
              }
            ]
          }
        }
      }
    ]
  },
  "filter": {
    "page": "users",
    "types": "all",
    "prefer_chart": "bar"
  }
}

```

---

## 📊 Available Response Helpers

Inside your Report classes you can use:

```php
$this->cardResponse($data);  // Computable to render as a card
$this->chartResponse('field', $data, 'bar'); // All types returned by default
$this->chartResponse('field', $data, 'bar'); 
$this->chartResponse('field', $data, 'pie');
$this->chartResponse('field', $data, 'line');
$this->chartResponse('field', $data, 'table');

```

---

## 🔧 Advanced Usage

* **Multiple Pages** → define `orders`, `sales`, `products` inside `config/report.php`.
* **Mixed Reports** → pass `types=['summary','by_status']` to `ReportBuilder` for custom dashboards.
* **Custom Chart Types** → set `prefer_chart = 'pie' | 'bar' | 'line'`.

---

## 🎨 Demo Frontend Integration

The JSON response is **frontend-agnostic**. You can render it in **Vue**, **React**, or **any JS chart library**.

## Usage / Examples

### Vue 3 + Highcharts

```vue
<script setup>
import Highcharts from "highcharts";
import HighchartsVue from "highcharts-vue";
import { ref, onMounted } from "vue";

const chartOptions = ref(null);

onMounted(async () => {
  const response = await fetch("reports?page=users");
  const data = await response.json();
  chartOptions.value = data.report.charts[0].data[data.report.charts[0].type];
});
</script>

<template>
  <div>
    <highcharts :options="chartOptions" v-if="chartOptions" />
  </div>
</template>
```

---

### React + Highcharts

```jsx
import React, { useEffect, useState } from "react";
import Highcharts from "highcharts";
import HighchartsReact from "highcharts-react-official";

export default function UserReport() {
  const [options, setOptions] = useState(null);

  useEffect(() => {
    fetch("/reports?page=users")
      .then(res => res.json())
      .then(data => {
        const chart = data.report.charts[0];
        setOptions(chart.data[chart.type]);
      });
  }, []);

  return options ? <HighchartsReact highcharts={Highcharts} options={options} /> : null;
}
```

---

### Vanilla JS + Highcharts

```html
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Report Example</title>
  <script src="https://code.highcharts.com/highcharts.js"></script>
</head>
<body>
  <div id="container" style="width:100%; height:400px;"></div>

  <script>
    fetch("/reports?page=users")
      .then(res => res.json())
      .then(data => {
        const chart = data.report.charts[0];
        const options = chart.data[chart.type];
        Highcharts.chart("container", options);
      });
  </script>
</body>
</html>
```

---

## 🤝 Contributing

Pull requests are welcome! For major changes, please open an issue first to discuss what you’d like to change.
Make sure to update tests as appropriate.

---

## ✅ Version Support

- **PHP**: 8.0 – 8.5
- **Laravel**: 8 – 12

---

## 📜 License

MIT © [Hasan Hawary](https://github.com/hasanhawary)
