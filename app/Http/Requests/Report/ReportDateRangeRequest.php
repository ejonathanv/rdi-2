<?php

namespace App\Http\Requests\Report;

use App\Models\Incident;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class ReportDateRangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Incident::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'from' => 'fecha desde',
            'to' => 'fecha hasta',
        ];
    }

    public function fromDate(): CarbonInterface
    {
        $validated = $this->validated();

        if (! empty($validated['from'])) {
            return Carbon::parse($validated['from'])->startOfDay();
        }

        return now()->subDays(29)->startOfDay();
    }

    public function toDate(): CarbonInterface
    {
        $validated = $this->validated();

        if (! empty($validated['to'])) {
            return Carbon::parse($validated['to'])->endOfDay();
        }

        return now()->endOfDay();
    }

    /**
     * @return array{from: string, to: string}
     */
    public function filterPayload(): array
    {
        return [
            'from' => $this->fromDate()->toDateString(),
            'to' => $this->toDate()->toDateString(),
        ];
    }
}
