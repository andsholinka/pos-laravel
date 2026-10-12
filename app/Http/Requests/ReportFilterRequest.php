<?php

namespace App\Http\Requests;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ReportFilterRequest extends FormRequest
{
    /** Ranges longer than this (in days) default to monthly grouping. */
    public const AUTO_MONTHLY_AFTER_DAYS = 62;

    /** Longest range (in days) that may be grouped daily; longer ones need Bulanan or Otomatis. */
    public const MAX_DAILY_DAYS = 366;

    /** Longest range of any kind, in years. */
    public const MAX_RANGE_YEARS = 10;

    /** Invalid filters send the user back to the report page (also for the PDF/CSV links). */
    protected $redirectRoute = 'reports.index';

    public function authorize(): bool
    {
        return $this->user()->can('admin');
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'group' => ['nullable', 'in:daily,monthly'],
        ];
    }

    /**
     * Range caps, checked once the formats are valid. Applies to the page, PDF and CSV alike,
     * since all three use this request.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                [$from, $to] = $this->resolveRange($this->input('from'), $this->input('to'));

                if ($to->gte($from->addYears(self::MAX_RANGE_YEARS))) {
                    $validator->errors()->add('to', 'Rentang tanggal maksimal '.self::MAX_RANGE_YEARS.' tahun.');

                    return;
                }

                if ($this->input('group') === 'daily' && (int) $from->diffInDays($to) + 1 > self::MAX_DAILY_DAYS) {
                    $validator->errors()->add('group', 'Rentang maksimal '.self::MAX_DAILY_DAYS.' hari untuk pengelompokan harian. Pilih Bulanan atau Otomatis.');
                }
            },
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'from' => 'tanggal mulai',
            'to' => 'tanggal akhir',
            'group' => 'pengelompokan',
        ];
    }

    /**
     * Inclusive date range; defaults to the first day of this month through today.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public function range(): array
    {
        return $this->resolveRange($this->validated('from'), $this->validated('to'));
    }

    /**
     * Shared by range() and the after() caps (validated() is not available inside the validator).
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function resolveRange(?string $fromInput, ?string $toInput): array
    {
        $to = filled($toInput)
            ? CarbonImmutable::createFromFormat('Y-m-d', $toInput)->startOfDay()
            : CarbonImmutable::today();

        $from = filled($fromInput)
            ? CarbonImmutable::createFromFormat('Y-m-d', $fromInput)->startOfDay()
            : $to->startOfMonth();

        // Only `from` given and it lies after today: show that single day instead of an empty range.
        if ($from->gt($to)) {
            $to = $from;
        }

        return [$from, $to];
    }

    /** 'daily' or 'monthly'; long ranges switch to monthly unless a group was chosen. */
    public function group(): string
    {
        if ($this->filled('group')) {
            return $this->validated('group');
        }

        [$from, $to] = $this->range();

        return (int) $from->diffInDays($to) + 1 > self::AUTO_MONTHLY_AFTER_DAYS ? 'monthly' : 'daily';
    }
}
