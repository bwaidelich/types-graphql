<?php

declare(strict_types=1);

namespace Wwwision\TypesGraphQL\Types;

final class ObjectTypeDefinition implements RootLevelDefinition
{
    public function __construct(
        public readonly string $name,
        public readonly FieldDefinitions $fieldDefinitions,
        public readonly ?string $description = null,
        public readonly ?Directives $directives = null,
        public readonly bool $isInputType = false,
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
        $type = $this->isInputType ? 'input' : 'type';
        $result .= "$type $this->name";
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
