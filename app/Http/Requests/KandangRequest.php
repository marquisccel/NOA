<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class KandangRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isPeternak();
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'nama_farm' => ['required', 'string', 'max:255'],
            'alamat' => ['nullable', 'string', 'max:1000'],
            'kapasitas' => ['required', 'integer', 'min:1', 'max:1000000'],
            'tipe' => ['nullable', 'string', 'max:30'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'nama_farm' => 'nama farm',
            'kapasitas' => 'kapasitas',
        ];
    }
}
