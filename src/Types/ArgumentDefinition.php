<?php

declare(strict_types=1);

namespace Wwwision\TypesGraphQL\Types;

final class ArgumentDefinition
{
    public function __construct(
        public readonly string $name,
        public readonly FieldType $type,
        public readonly ?string $description = null,
        public readonly ?ArgumentValue $defaultValue = null,
        public readonly ?Directives $directives = null,
    ) {
    }

    public function render(): string
    {
        $result = '';
        if ($this->description !== null) {
            $result .= "\"\"\" $this->description \"\"\" ";
        }
        $result .= $this->name;
        $result .= ': ' . $this->type->render();
        if ($this->defaultValue !== null) {
            $result .= ' = ' . $this->defaultValue->render();
        }
        if ($this->directives !== null) {
            $result .= ' ' . $this->directives->render();
        }
        return $result;
    }
}
