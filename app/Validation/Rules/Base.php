<?php

declare(strict_types=1);

namespace Devanox\Core\Validation\Rules;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

abstract class Base
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, Rule|array<mixed>|string>
     */
    abstract public static function rules(): array;

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [];
    }

    /**
     * Get the custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public static function attributes(): array
    {
        return [];
    }

    /**
     * A reusable validation method that can be called from any class that extends this base class.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public static function validate(array $data, ?string $errorBag = null): array
    {
        $validator = Validator::make(
            $data,
            static::rules(),
            static::messages(),
            static::attributes(),
        );

        if ($errorBag !== null) {
            $validator->validateWithBag($errorBag);
        }

        /** @var array<string, mixed> $validated */
        $validated = $validator->validate();

        return $validated;
    }
}
