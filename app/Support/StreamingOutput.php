<?php

declare(strict_types=1);

namespace Devanox\Core\Support;

use Closure;
use Override;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Output\Output;

use const PHP_EOL;

final class StreamingOutput extends Output
{
    public function __construct(
        private readonly Closure $callback,
        int $verbosity = self::VERBOSITY_NORMAL,
        bool $decorated = false,
        ?OutputFormatterInterface $formatter = null,
    ) {
        parent::__construct($verbosity, $decorated, $formatter);
    }

    #[Override]
    public function doWrite(string $message, bool $newline): void
    {
        ($this->callback)($message . ($newline ? PHP_EOL : ''));
    }
}
