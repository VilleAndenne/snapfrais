<?php

namespace App\Http\Requests\Settings;

use App\Rules\Iban;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class PaymentDetailsRequest extends FormRequest
{
    /**
     * Normalise l'IBAN avant validation : les agents le saisissent aussi bien
     * par blocs de quatre qu'en un seul bloc. On stocke la forme canonique.
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('bank_account_number'))) {
            $this->merge([
                'bank_account_number' => strtoupper(
                    preg_replace('/[\s.-]+/', '', $this->input('bank_account_number')) ?? ''
                ),
            ]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'bank_account_number' => ['required', 'string', 'max:255', new Iban],
            'address' => ['required', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'bank_account_number.required' => 'Veuillez indiquer le numéro de compte sur lequel vous être remboursé.',
            'address.required' => 'Veuillez indiquer votre adresse complète.',
        ];
    }
}
