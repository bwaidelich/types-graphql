<?php

declare(strict_types=1);

namespace Wwwision\TypesGraphQL\Types;

final class FieldDefinition
{
    public function __construct(
        public readonly string $name,
        public readonly FieldType $type,
        public readonly ?string $description = null,
        public readonly ?ArgumentDefinitions $argumentDefinitions = null,
    ) {
    }

    public function render(): string
    {
        $result = '';
        if ($this->description !== null) {
            $result .= "\"\"\" $this->description \"\"\"\n  ";
        }
        $result .= $this->name;
        if ($this->argumentDefinitions !== null) {
            $result .= '(' . $this->argumentDefinitions->render() . ')';
        }
        $result .= ': ' . $this->type->render();
        return $result;
    }
}
