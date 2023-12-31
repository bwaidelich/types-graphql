<?php

declare(strict_types=1);

namespace Wwwision\TypesGraphQL\Types;

final class ObjectTypeDefinition implements RootLevelDefinition
{
    /**
     * @param array<string> $implementsInterfaces
     */
    public function __construct(
        public readonly string $name,
        public readonly FieldDefinitions $fieldDefinitions,
        public readonly ?string $description = null,
        public readonly ?Directives $directives = null,
        public readonly bool $isInputType = false,
        private array $implementsInterfaces = [],
    ) {
    }

    public function implementsInterface(string $interfaceName): void
    {
        $this->implementsInterfaces[] = $interfaceName;
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
        if ($this->implementsInterfaces !== []) {
            $result .= " implements " . implode(' & ', $this->implementsInterfaces);
        }
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
