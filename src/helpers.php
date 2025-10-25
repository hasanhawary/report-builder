<?php

use Illuminate\Support\Str;

if (!function_exists('rb_resolve_trans')) {
    /**
     * Resolve translation strictly from the application's lang files.
     * Example: resources/lang/{locale}/{page}.php
     * Falls back to the original string if the key is missing.
     */
    function rb_resolve_trans($trans = '', $page = null, $lang = null, $snaked = true): ?string
    {
        $page = $page ?? config('report.translate.file', 'report');

        if (empty($trans)) {
            return '---';
        }

        app()->setLocale($lang ?? app()->getLocale());

        $key = $snaked ? Str::snake($trans) : $trans;

        $line = __("$page.$key");
        return Str::startsWith($line, "$page.") ? $trans : $line;
    }
}
