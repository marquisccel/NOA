<?php

namespace App\Http\Requests;

use App\Services\LinkPeta;
use Illuminate\Foundation\Http\FormRequest;

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
            'link_gmaps' => ['nullable', 'string', 'max:2048', function (string $atribut, mixed $nilai, \Closure $gagal) {
                if (! LinkPeta::hostGoogle(trim((string) $nilai)) || ! filter_var(trim((string) $nilai), FILTER_VALIDATE_URL)) {
                    $gagal('Link Google Maps tidak valid. Salin dari tombol Bagikan di Google Maps (diawali https://).');
                }
            }],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->link_gmaps)) {
            $this->merge(['link_gmaps' => trim($this->link_gmaps)]);
        }
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
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'nama_farm' => 'nama kandang',
            'alamat' => 'alamat kandang',
            'kapasitas' => 'kapasitas',
            'link_gmaps' => 'link Google Maps',
        ];
    }
}
