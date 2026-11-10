<?php

namespace App\Http\Requests;

use App\Models\Kandang;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class KandangRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isPeternak();
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'nama_farm' => ['required', 'string', 'max:255'],
            'alamat' => ['required', 'string', 'max:1000'],
            'kapasitas' => ['required', 'integer', 'min:1', 'max:1000000'],
            'tipe' => ['required', Rule::in(array_keys(Kandang::TIPE))],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'nama_farm.required' => 'Nama kandang wajib diisi.',
            'alamat.required' => 'Alamat kandang wajib diisi.',
            'kapasitas.required' => 'Kapasitas wajib diisi.',
            'kapasitas.integer' => 'Kapasitas harus berupa angka lebih dari 0.',
            'kapasitas.min' => 'Kapasitas harus berupa angka lebih dari 0.',
            'tipe.required' => 'Pilih salah satu tipe kandang.',
            'tipe.in' => 'Pilih salah satu tipe kandang.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'nama_farm' => 'nama kandang',
            'alamat' => 'alamat kandang',
            'kapasitas' => 'kapasitas',
            'tipe' => 'tipe kandang',
        ];
    }
}
