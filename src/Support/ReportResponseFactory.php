<?php

namespace HasanHawary\ReportBuilder\Support;

use Illuminate\Http\JsonResponse;

class ReportResponseFactory
{
    public function success(mixed $data = [], ?string $message = null, int $code = 200): JsonResponse
    {
        return response()->json($this->envelope(true, $code, $message ?? trans('report-builder::messages.success'), $data), $code);
    }

    public function error(string $message, mixed $data = [], int $code = 400): JsonResponse
    {
        return response()->json($this->envelope(false, $code, $message, $data), $code);
    }

    public function validation(array $errors): JsonResponse
    {
        return $this->error((string) collect($errors)->flatten()->first(), [
            'errors' => $errors,
        ], 422);
    }

    private function envelope(bool $status, int $code, string $message, mixed $data): array
    {
        return [
            'status' => $status,
            'code' => $code,
            'message' => $message,
            'data' => $data,
        ];
    }
}
