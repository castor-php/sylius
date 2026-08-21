<?php

namespace Castor\Sylius;

final readonly class App
{
    public function __construct(
        private string $name,
        private string $directory,
    ) {}

    public function name(): string
    {
        return $this->name;
    }

    public function directory(): string
    {
        return $this->directory;
    }
}
