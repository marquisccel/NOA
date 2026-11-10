<?php

namespace App\Http\Requests;

use App\Models\PemberianOvk;
use App\Models\Siklus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OvkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isPeternak();
    }

    public function siklus(): Siklus
    {
        return $this->route('siklus') ?? $this->route('ovk')->siklus;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $masuk = $this->siklus()->tanggal_masuk->toDateString();

        return [
            'tanggal' => ['required', 'date', "after_or_equal:{$masuk}", 'before_or_equal:today'],
            'jenis' => ['required', Rule::in(PemberianOvk::JENIS)],
            'nama_produk' => ['required', 'string', 'max:255'],
            'dosis' => ['nullable', 'string', 'max:255'],
            'biaya' => ['required', 'numeric', 'min:0', 'max:9999999999999'],
        ];
    }

    /** Biaya kosong dianggap nol agar pemberian tetap tercatat. */
    protected function prepareForValidation(): void
    {
        if ($this->input('biaya') === null || $this->input('biaya') === '') {
            $this->merge(['biaya' => 0]);
        }
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'nama_produk' => 'nama produk',
        ];
    }
}
