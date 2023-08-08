<?php

declare(strict_types=1);

namespace Wwwision\TypesGraphQL\Types;

final class InterfaceDefinition implements RootLevelDefinition
{
    public function __construct(
        public readonly string $name,
        public readonly FieldDefinitions $fieldDefinitions,
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
            $result .= "\"\"\" $this->description \"\"\"\n";
        }
        $result .= "interface $this->name";
        if ($this->directives !== null) {
            $result .= ' ' . $this->directives->render();
        }
        $result .= " {\n";
        foreach ($this->fieldDefinitions as $fieldDefinition) {
            $result .= '  ' . $fieldDefinition->render() . "\n";
        }
        $result .= "}\n";
        return $result;
    }
}
