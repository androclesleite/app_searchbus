<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmBookingRequest extends FormRequest
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
            'travelId' => ['required', 'string'],
            'selectedSeats' => ['required', 'array', 'min:1'],
            'selectedSeats.*' => ['string'],
            'tripData' => ['required', 'array'],
        ];
    }

    // Mensagens de erro customizadas
    public function messages(): array
    {
        return [
            'travelId.required' => 'ID da viagem é obrigatório',
            'selectedSeats.required' => 'Selecione pelo menos um assento',
            'selectedSeats.min' => 'Selecione pelo menos um assento',
            'tripData.required' => 'Dados da viagem são obrigatórios',
        ];
    }
}
