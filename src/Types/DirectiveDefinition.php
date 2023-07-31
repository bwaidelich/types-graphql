<?php

declare(strict_types=1);

namespace Wwwision\TypesGraphQL\Types;

final class DirectiveDefinition implements RootLevelDefinition
{
    public function __construct(
        private readonly string $name,
        public readonly DirectiveLocations $locations,
        public readonly ?ArgumentDefinitions $argumentDefinitions = null,
        public readonly ?string $description = null,
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
        $result .= "directive @{$this->getName()}";
        if ($this->argumentDefinitions !== null) {
            $result .= '(' . $this->argumentDefinitions->render() . ')';
        }
        $result .= ' on ' . $this->locations->render() . "\n\n";
        return $result;
    }
}
