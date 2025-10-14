<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SelectSeatRequest extends FormRequest
{
    // Autoriza todas as requisições
    public function authorize(): bool
    {
        return true;
    }

    // Regras de validação
    public function rules(): array
    {
        return [
            'from' => ['sometimes', 'string'],
            'to' => ['sometimes', 'string'],
            'data' => ['sometimes', 'date'],
        ];
    }
}
