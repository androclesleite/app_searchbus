<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchTripRequest extends FormRequest
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
            'from' => ['required', 'string'],
            'to' => ['required', 'string', 'different:from'],
            'data' => ['required', 'date', 'after_or_equal:today'],
        ];
    }

    // Mensagens de erro customizadas
    public function messages(): array
    {
        return [
            'from.required' => 'Selecione a cidade de origem',
            'to.required' => 'Selecione a cidade de destino',
            'to.different' => 'Origem e destino não podem ser iguais',
            'data.required' => 'Selecione a data de viagem',
            'data.date' => 'Data inválida',
            'data.after_or_equal' => 'A data deve ser hoje ou futura',
        ];
    }

    // Nomes dos atributos
    public function attributes(): array
    {
        return [
            'from' => 'origem',
            'to' => 'destino',
            'data' => 'data de viagem',
        ];
    }
}
