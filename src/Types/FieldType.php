<?php

declare(strict_types=1);

namespace Wwwision\TypesGraphQL\Types;

final class FieldType
{
    public function __construct(
        public readonly string $name,
        public readonly bool $required = false,
        public readonly bool $isList = false,
        public readonly ?Directives $directives = null,
    ) {
    }

    public function render(): string
    {
        $result = $this->name;
        if ($this->isList) {
            $result = '[' . $result . '!]';
        }
        if ($this->required) {
            $result .= '!';
        }
        if ($this->directives !== null) {
            $result .= ' ' . $this->directives->render();
        }
        return $result;
    }
}
