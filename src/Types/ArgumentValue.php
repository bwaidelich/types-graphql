<?php

declare(strict_types=1);

namespace Wwwision\TypesGraphQL\Types;

use Stringable;

use function is_bool;

final class ArgumentValue
{
    public function __construct(
        public readonly int|float|string|bool|Stringable $value,
    ) {
    }

    public function render(): string
    {
        if (is_string($this->value)) {
            return '"' . addslashes($this->value) . '"';
        }
        if (is_bool($this->value)) {
            return $this->value ? 'true' : 'false';
        }
        return (string)$this->value;
    }
}
