<?php

namespace Devanox\Core\Support;

use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Output\Output;

class StreamingOutput extends Output
{
    protected $callback;

    public function __construct(callable $callback, int $verbosity = self::VERBOSITY_NORMAL, bool $decorated = false, ?OutputFormatterInterface $formatter = null)
    {
        parent::__construct($verbosity, $decorated, $formatter);
        $this->callback = $callback;
    }

    protected function doWrite(string $message, bool $newline): void
    {
        $output = $message . ($newline ? "\n" : '');
        call_user_func($this->callback, $output);
    }
}
