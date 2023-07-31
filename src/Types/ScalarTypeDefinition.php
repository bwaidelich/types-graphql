<?php

declare(strict_types=1);

namespace Wwwision\TypesGraphQL\Types;

final class ScalarTypeDefinition implements RootLevelDefinition
{
    public function __construct(
        public readonly string $name,
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
        $description = '';
        if ($this->description !== null) {
            $description = $this->description . "\n";
        }
        if ($this->directives !== null) {
            $directivesDescription = $this->directives->getDescription();
            if ($directivesDescription !== '') {
                $description .= "\n" . $directivesDescription;
            }
        }
        if ($description !== '') {
            $result .= "\"\"\"\n$description\"\"\"\n";
        }
        $result .= "scalar $this->name";
        if ($this->directives !== null) {
            $result .= ' ' . $this->directives->render();
        }
        $result .= "\n";
        return $result;
    }
}
