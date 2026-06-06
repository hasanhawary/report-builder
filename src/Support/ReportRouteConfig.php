<?php

namespace HasanHawary\ReportBuilder\Support;

use Illuminate\Support\Arr;

class ReportRouteConfig
{
    private ?array $routes = null;

    public function enabled(): bool
    {
        return (bool) Arr::get($this->routes(), 'enabled');
    }

    public function prefix(): ?string
    {
        $prefix = trim((string) Arr::get($this->routes(), 'prefix'), '/');

        return $prefix === '' ? null : $prefix;
    }

    public function middleware(): array
    {
        $middleware = Arr::get($this->routes(), 'middleware', []);

        return $middleware === null ? [] : Arr::wrap($middleware);
    }

    public function namePrefix(): string
    {
        $prefix = trim((string) Arr::get($this->routes(), 'name_prefix'), '.');

        return $prefix === '' ? '' : $prefix . '.';
    }

    public function path(string $key): string
    {
        $path = trim((string) Arr::get($this->routes(), "paths.{$key}"), '/');

        return $path === '' ? '/' : $path;
    }

    public function name(string $key): string
    {
        return trim((string) Arr::get($this->routes(), "names.{$key}"), '.');
    }

    private function routes(): array
    {
        if ($this->routes !== null) {
            return $this->routes;
        }

        $routes = config('report.routes', []);

        if (!is_array($routes)) {
            return $this->routes = $this->packageRoutes();
        }

        $merged = array_replace_recursive($this->packageRoutes(), $routes);

        if (array_key_exists('middleware', $routes)) {
            $merged['middleware'] = $routes['middleware'];
        }

        return $this->routes = $merged;
    }

    private function packageRoutes(): array
    {
        $config = require __DIR__ . '/../../config/report.php';

        return $config['routes'] ?? [];
    }
}
