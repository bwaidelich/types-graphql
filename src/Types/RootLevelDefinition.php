<?php

declare(strict_types=1);

namespace Wwwision\TypesGraphQL\Types;

interface RootLevelDefinition
{
    public function getName(): string;
    public function render(): string;
}
