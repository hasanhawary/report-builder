<?php

declare(strict_types=1);

namespace HasanHawary\ReportBuilder\Trait;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

trait ResponsesTrait
{
    /**
     * Handle transformation of card item.
     */
    public function cardResponse($cards): array
    {
        $callerFunctionName = debug_backtrace()[1]['function'];
        $keyName = Str::snake(str_replace('get', '', $callerFunctionName));

        $cards = collect($cards)->map(fn($value, $key) => [
            'key' => $key,
            'value' => $value,
            'label' => $this->resolveTrans($key),
            'size' => $this->mixedCharts[$keyName]['size'] ?? $this->charts[$keyName]['size'] ?? ['cols' => '6', 'md' => '4', 'lg' => '4'],
        ])->values()->toArray();

        return $this->prepareResponse($keyName, $cards);
    }

    /**
     * Prepare chart data to be returned to the front-end.
     */
    public function chartResponse(string $groupByField, array|Collection $data = []): array
    {
        $callerFunctionName = debug_backtrace()[1]['function'];
        $keyName = Str::snake(str_replace('get', '', $callerFunctionName));

        $configChart = $this->mixedCharts[$keyName] ?? $this->charts[$keyName];
        $isTable = $configChart['type'] === 'table';

        $data = $this->chartHandler->resolve($groupByField, $data, $isTable ? 'table' : 'all');

        return $this->prepareResponse($keyName, $data);
    }

    /**
     * Prepare the response data to be returned to the front-end.
     */
    private function prepareResponse($keyName, $response): array
    {
        $data = $this->mixedCharts[$keyName] ?? $this->charts[$keyName];
        $data['title'] = $this->resolveTrans($data['title'] ?? $keyName);
        $data['data'] = $response;
        $data['size'] = $this->mixedCharts[$keyName]['size'] ?? $this->charts[$keyName]['size'] ?? ['cols' => '12', 'md' => '6', 'lg' => '6'];

        if (@$data['type'] === 'table') {
            $data['columns'] = collect(array_keys((array)Arr::first($response)))->map(function ($item) {
                return [
                    'title' => $this->resolveTrans($item),
                    'key' => $item,
                ];
            });
        }

        return $data;
    }

    public function resolveTrans($trans = '', $page = 'report', $lang = null, $snaked = true): ?string
    {
        if (empty($trans)) {
            return '---';
        }

        app()->setLocale($lang ?? app()->getLocale());

        $key = $snaked ? Str::snake($trans) : $trans;

        if (function_exists('__')) {
            return Str::startsWith(__("$page.$key"), "$page.") ? $trans : __("$page.$key");
        }
        return $trans;
    }
}
