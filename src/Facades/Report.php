<?php

namespace HasanHawary\ReportBuilder\Facades;

use HasanHawary\ReportBuilder\ReportBuilder;
use Illuminate\Support\Facades\Facade;

/**
 * Facade for the ReportBuilder.
 *
 * @method static mixed response()
 * @method static mixed resolve(string $path, bool $mixed = false, ?array $filterOverride = null)
 * @see \HasanHawary\ReportBuilder\ReportBuilder
 */
class Report extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        // Return the concrete class so Laravel can auto-resolve it without explicit binding
        return ReportBuilder::class;
    }
}
