<?php

use HasanHawary\ReportBuilder\Http\Controllers\ReportController;
use HasanHawary\ReportBuilder\Support\ReportRouteConfig;
use Illuminate\Support\Facades\Route;

$routes = app(ReportRouteConfig::class);

Route::get($routes->path('report'), ReportController::class)
    ->name($routes->name('report'));
