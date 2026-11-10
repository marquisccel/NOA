<?php

namespace App\Http\Requests;

use App\Models\Siklus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SelesaiSiklusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isPeternak();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $masuk = $this->route('siklus')->tanggal_masuk->toDateString();

        return [
            'status' => ['required', Rule::in([Siklus::STATUS_SELESAI, Siklus::STATUS_DIHENTIKAN])],
            'tanggal_panen' => ['required', 'date', "after_or_equal:{$masuk}"],
            'tanggal_rechick' => ['nullable', 'date', 'after_or_equal:tanggal_panen'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'tanggal_panen' => 'tanggal panen',
            'tanggal_rechick' => 'tanggal re-chick in',
        ];
    }
}
