<?php

declare(strict_types=1);

namespace Wwwision\TypesGraphQL\Types;

final class EnumTypeDefinition implements RootLevelDefinition
{
    public function __construct(
        public readonly string $name,
        public readonly EnumValueDefinitions $valueDefinitions,
        public readonly ?string $description = null,
        public readonly ?Directives $directives = null,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function render(): string
    {
        $result = '';
        if ($this->description !== null) {
            $result .= "\"\"\"\n$this->description\n\"\"\"\n";
        }
        $result .= "enum {$this->getName()}";
        if ($this->directives !== null) {
            $result .= ' ' . $this->directives->render();
        }
        $result .= " {\n";
        $result .= $this->valueDefinitions->render();
        $result .= "}\n";
        return $result;
    }
}
