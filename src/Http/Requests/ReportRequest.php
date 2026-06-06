<?php

namespace HasanHawary\ReportBuilder\Http\Requests;

use HasanHawary\ReportBuilder\Support\ReportChartType;
use HasanHawary\ReportBuilder\Support\ReportResponseFactory;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->replace(collect($this->all())
            ->map(fn ($value) => $value === '' ? null : $value)
            ->toArray());
    }

    public function rules(): array
    {
        return [
            'start' => ['nullable', 'date'],
            'end' => ['nullable', 'date', 'after_or_equal:start'],
            'page' => ['nullable', 'string'],
            'advanced' => ['nullable', 'array'],
            'config' => ['nullable', 'array'],
            'types' => ['nullable'],
            'prefer_chart' => ['nullable', 'string', Rule::in(ReportChartType::values())],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        $errors = (new ValidationException($validator))->errors();

        throw new HttpResponseException(app(ReportResponseFactory::class)->validation($errors));
    }
}
