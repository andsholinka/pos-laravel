<?php

namespace App\Http\Requests;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ReportFilterRequest extends FormRequest
{
    /** Ranges longer than this (in days) default to monthly grouping. */
    public const AUTO_MONTHLY_AFTER_DAYS = 62;

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
        $to = $this->filled('to')
            ? CarbonImmutable::createFromFormat('Y-m-d', $this->validated('to'))->startOfDay()
            : CarbonImmutable::today();

        $from = $this->filled('from')
            ? CarbonImmutable::createFromFormat('Y-m-d', $this->validated('from'))->startOfDay()
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
