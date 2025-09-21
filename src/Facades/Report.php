<?php

namespace HasanHawary\ReportGenerator\Facades;

use HasanHawary\ReportGenerator\ReportBuilder;
use Illuminate\Support\Facades\Facade;

/**
 * Facade for the ReportBuilder.
 *
 * @method static mixed response()
 * @method static mixed resolve(string $path, bool $mixed = false, ?array $filterOverride = null)
 * @see \HasanHawary\ReportGenerator\ReportBuilder
 */
class Report extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        // Return the concrete class so Laravel can auto-resolve it without explicit binding
        return ReportBuilder::class;
    }
}
