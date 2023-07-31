<?php

declare(strict_types=1);

namespace Wwwision\TypesGraphQL\Types;

final class EnumValueDefinition
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $description = null,
    ) {
    }

    public function render(): string
    {
        $result = '';
        if ($this->description !== null) {
            $result .= "\"\"\"\n$this->description\n\"\"\"\n";
        }
        $result .= $this->name;
        return $result;
    }
}
