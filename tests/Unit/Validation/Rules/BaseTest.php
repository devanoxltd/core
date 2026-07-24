<?php

declare(strict_types=1);

use Devanox\Core\Validation\Rules\Base;
use Illuminate\Validation\ValidationException;

final class TestRules extends Base
{
    public static function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'age' => 'nullable|integer|min:18',
        ];
    }

    public static function attributes(): array
    {
        return [
            'name' => 'full name',
        ];
    }
}

it('validates correct data', function (): void {
    $validated = TestRules::validate([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'age' => 25,
    ]);

    expect($validated)->toBe([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'age' => 25,
    ]);
});

it('throws validation exception for invalid data', function (): void {
    expect(fn (): array => TestRules::validate(['name' => '', 'email' => 'not-an-email']))
        ->toThrow(ValidationException::class);
});

it('uses custom attributes in errors', function (): void {
    try {
        TestRules::validate(['name' => '']);
    } catch (ValidationException $validationException) {
        expect($validationException->errors()['name'][0])->toContain('full name');
    }
});

it('validates with named error bag', function (): void {
    expect(fn (): array => TestRules::validate(['name' => ''], 'custom'))
        ->toThrow(ValidationException::class);
});

final class TestRulesWithoutAttributes extends Base
{
    public static function rules(): array
    {
        return ['name' => 'required'];
    }
}

it('returns empty attributes by default', function (): void {
    expect(TestRulesWithoutAttributes::attributes())->toBe([]);
});
