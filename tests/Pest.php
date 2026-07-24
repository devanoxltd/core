<?php

declare(strict_types=1);

use Devanox\Core\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

pest()->project()->github('devanoxltd/core');

if (! function_exists('testTempPath')) {
    function testTempPath(string $path = ''): string
    {
        return __DIR__ . '/temp' . ($path !== '' && $path !== '0' ? DIRECTORY_SEPARATOR . $path : '');
    }
}
