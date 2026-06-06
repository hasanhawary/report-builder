<?php

namespace HasanHawary\ReportBuilder\Http\Controllers;

use HasanHawary\ReportBuilder\Exceptions\ReportConfigurationException;
use HasanHawary\ReportBuilder\Http\Requests\ReportRequest;
use HasanHawary\ReportBuilder\ReportBuilder;
use HasanHawary\ReportBuilder\Support\ReportFilterResolver;
use HasanHawary\ReportBuilder\Support\ReportResponseFactory;
use InvalidArgumentException;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use RuntimeException;

class ReportController extends Controller
{
    public function __invoke(
        ReportRequest $request,
        ReportFilterResolver $filters,
        ReportResponseFactory $responses
    ): JsonResponse
    {
        try {
            $report = new ReportBuilder($filters->resolve($request->validated()));

            return $responses->success($report->response());
        } catch (InvalidArgumentException $e) {
            return $responses->error($e->getMessage(), [], 422);
        } catch (ReportConfigurationException $e) {
            return $responses->error($e->getMessage(), [], 404);
        } catch (RuntimeException $e) {
            return $responses->error($e->getMessage(), [], 500);
        }
    }
}
