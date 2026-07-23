<?php

namespace App\Support;

use Closure;
use Illuminate\Validation\Rule;

/**
 * Reglas y mensajes reutilizables para formularios AUTOFIX IA.
 */
class FieldValidation
{
    /** Letras (con acentos), espacios, apóstrofe y guion */
    public const NOMBRE_REGEX = "/^[\p{L}]+(?:[ '\-][\p{L}]+)*$/u";

    /** Exactamente 10 dígitos */
    public const TELEFONO_REGEX = '/^[0-9]{10}$/';

    /** Placa: letras/números y guion opcional, 5–8 caracteres */
    public const PLACA_REGEX = '/^[A-Za-z0-9]{3}[-]?[A-Za-z0-9]{2,4}$/';

    public static function nombre(bool $required = true): array
    {
        return array_values(array_filter([
            $required ? 'required' : 'nullable',
            'string',
            'min:2',
            'max:100',
            'regex:' . self::NOMBRE_REGEX,
        ]));
    }

    public static function telefono(bool $required = true): array
    {
        return array_values(array_filter([
            $required ? 'required' : 'nullable',
            'string',
            'regex:' . self::TELEFONO_REGEX,
        ]));
    }

    public static function email(bool $required = true, ?string $uniqueTable = null, ?string $ignoreId = null): array
    {
        $rules = array_values(array_filter([
            $required ? 'required' : 'nullable',
            'string',
            'email',
            'max:255',
        ]));

        if ($uniqueTable) {
            $unique = Rule::unique($uniqueTable, 'email');
            if ($ignoreId) {
                $unique->ignore($ignoreId);
            }
            $rules[] = $unique;
        }

        return $rules;
    }

    public static function placa(bool $required = true, ?string $ignoreId = null): array
    {
        $unique = Rule::unique('vehiculos', 'placa');
        if ($ignoreId) {
            $unique->ignore($ignoreId);
        }

        return array_values(array_filter([
            $required ? 'required' : 'nullable',
            'string',
            'max:20',
            'regex:' . self::PLACA_REGEX,
            $unique,
        ]));
    }

    /**
     * Valida número de documento según tipo (CEDULA/DNI/RUC/CE/PASAPORTE).
     */
    public static function documentoPorTipo(string $tipoField = 'tipo_documento'): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($tipoField): void {
            $tipo = strtoupper((string) request()->input($tipoField, ''));
            $valor = trim((string) $value);

            if ($valor === '') {
                return;
            }

            match ($tipo) {
                'CEDULA', 'DNI' => self::assertDigitsBetween($valor, 6, 10, $fail, 'La cédula/DNI debe tener entre 6 y 10 dígitos'),
                'RUC' => self::assertDigitsBetween($valor, 11, 13, $fail, 'El RUC debe tener entre 11 y 13 dígitos'),
                'CE' => self::assertAlphaNumBetween($valor, 6, 12, $fail, 'El carnet de extranjería debe tener entre 6 y 12 caracteres alfanuméricos'),
                'PASAPORTE' => self::assertAlphaNumBetween($valor, 6, 12, $fail, 'El pasaporte debe tener entre 6 y 12 caracteres alfanuméricos'),
                default => self::assertDigitsBetween($valor, 6, 15, $fail, 'El documento debe tener entre 6 y 15 dígitos'),
            };
        };
    }

    /**
     * Documento simple (mecánicos): solo dígitos, 6–10.
     */
    public static function documentoIdentidad(bool $required = true, ?string $uniqueTable = null, ?string $ignoreId = null): array
    {
        $rules = array_values(array_filter([
            $required ? 'required' : 'nullable',
            'string',
            'regex:/^[0-9]{6,10}$/',
        ]));

        if ($uniqueTable) {
            $unique = Rule::unique($uniqueTable, 'documento');
            if ($ignoreId) {
                $unique->ignore($ignoreId);
            }
            $rules[] = $unique;
        }

        return $rules;
    }

    public static function messages(): array
    {
        return [
            'regex' => 'El formato de :attribute no es válido',
            'nombres.regex' => 'Los nombres solo pueden contener letras y espacios',
            'apellidos.regex' => 'Los apellidos solo pueden contener letras y espacios',
            'name.regex' => 'El nombre solo puede contener letras y espacios',
            'telefono.regex' => 'El teléfono debe tener exactamente 10 dígitos numéricos',
            'documento.regex' => 'El documento debe tener entre 6 y 10 dígitos numéricos',
            'placa.regex' => 'La placa no tiene un formato válido (ej: ABC123 o ABC-123)',
            'email.email' => 'El correo electrónico no es válido',
            'min' => 'El campo :attribute es demasiado corto',
            'max' => 'El campo :attribute es demasiado largo',
            'numeric' => 'El campo :attribute debe ser un número',
            'integer' => 'El campo :attribute debe ser un número entero',
        ];
    }

    private static function assertDigitsBetween(string $value, int $min, int $max, Closure $fail, string $message): void
    {
        if (!preg_match('/^[0-9]+$/', $value) || strlen($value) < $min || strlen($value) > $max) {
            $fail($message);
        }
    }

    private static function assertAlphaNumBetween(string $value, int $min, int $max, Closure $fail, string $message): void
    {
        if (!preg_match('/^[A-Za-z0-9]+$/', $value) || strlen($value) < $min || strlen($value) > $max) {
            $fail($message);
        }
    }

    /** Normaliza teléfono/documento quitando espacios y guiones. */
    public static function soloDigitos(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $value);

        return $digits === '' ? null : $digits;
    }
}
