<?php

namespace HasanHawary\ReportBuilder\Tests\Unit;

use HasanHawary\ReportBuilder\Support\ReportResponseFactory;
use HasanHawary\ReportBuilder\Tests\TestCase;

class ReportResponseFactoryTest extends TestCase
{
    public function test_it_owns_success_and_error_envelopes(): void
    {
        $responses = app(ReportResponseFactory::class);

        $this->assertSame([
            'status' => true,
            'code' => 200,
            'message' => 'Success',
            'data' => ['ok' => true],
        ], $responses->success(['ok' => true])->getData(true));

        $this->assertSame([
            'status' => false,
            'code' => 422,
            'message' => 'Invalid',
            'data' => [],
        ], $responses->error('Invalid', [], 422)->getData(true));
    }

    public function test_it_owns_validation_error_envelopes(): void
    {
        $response = app(ReportResponseFactory::class)->validation([
            'page' => ['The page field is required.'],
        ]);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame([
            'status' => false,
            'code' => 422,
            'message' => 'The page field is required.',
            'data' => [
                'errors' => [
                    'page' => ['The page field is required.'],
                ],
            ],
        ], $response->getData(true));
    }
}
