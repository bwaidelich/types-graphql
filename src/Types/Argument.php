<?php

declare(strict_types=1);

namespace Wwwision\TypesGraphQL\Types;

final class Argument
{
    public function __construct(
        public readonly string $name,
        public readonly ArgumentValue $value,
    ) {
    }

    public function render(): string
    {
        return "$this->name: " . $this->value->render();
    }
}
