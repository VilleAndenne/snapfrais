<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class Iban implements ValidationRule
{
    /**
     * Valide un IBAN : format général, puis somme de contrôle modulo 97
     * (norme ISO 13616). Un IBAN mal saisi produirait un virement rejeté,
     * découvert bien après l'envoi de la demande à la comptabilité.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('Le numéro de compte doit être un IBAN valide.');

            return;
        }

        $iban = strtoupper(preg_replace('/[\s.-]+/', '', $value) ?? '');

        if (preg_match('/^[A-Z]{2}\d{2}[A-Z0-9]{10,30}$/', $iban) !== 1) {
            $fail('Le numéro de compte doit être un IBAN valide (par exemple BE68 5390 0754 7034).');

            return;
        }

        if (! $this->checksumIsValid($iban)) {
            $fail('Ce numéro de compte est incorrect : vérifiez votre saisie.');
        }
    }

    /**
     * Déplace les quatre premiers caractères en fin de chaîne, convertit les
     * lettres en nombres (A = 10 … Z = 35) et vérifie que le reste de la
     * division par 97 vaut 1.
     */
    private function checksumIsValid(string $iban): bool
    {
        $rearranged = substr($iban, 4).substr($iban, 0, 4);

        $numeric = '';
        foreach (str_split($rearranged) as $character) {
            $numeric .= ctype_alpha($character)
                ? (string) (ord($character) - 55)
                : $character;
        }

        // bcmod n'est pas garanti : on calcule le modulo par tranches.
        $remainder = 0;
        foreach (str_split($numeric, 7) as $chunk) {
            $remainder = (int) (((string) $remainder).$chunk) % 97;
        }

        return $remainder === 1;
    }
}
