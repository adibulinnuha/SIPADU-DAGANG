<?php

namespace App\Http\Requests;

use App\Rules\EretNumberRule;
use Illuminate\Foundation\Http\FormRequest;

class EretDashboardSaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $numericRule = ['nullable', new EretNumberRule];

        return [
            'tanggal' => ['required', 'date'],
            'rows' => ['required', 'array'],
            'rows.*.id' => ['nullable', 'integer'],
            'rows.*.market_id' => ['required', 'integer', 'exists:markets,id'],
            'rows.*.petugas_id' => ['required', 'integer', 'exists:users,id'],
            'rows.*.nomor_setor' => ['nullable', 'string', 'max:100'],
            'rows.*.entry_type' => ['nullable', 'string', 'in:manual,eret'],
            'rows.*.kios' => $numericRule,
            'rows.*.los' => $numericRule,
            'rows.*.dasaran' => $numericRule,
            'rows.*.mck' => $numericRule,
            'rows.*.sampah' => $numericRule,
            'rows.*.listrik' => $numericRule,
            'rows.*.notes' => ['nullable', 'string'],
        ];
    }
}

