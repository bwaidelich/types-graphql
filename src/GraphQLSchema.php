<?php

declare(strict_types=1);

namespace Wwwision\TypesGraphQL;

use Wwwision\TypesGraphQL\Types\RootLevelDefinitions;

final class GraphQLSchema
{
    public function __construct(
        public readonly RootLevelDefinitions $definitions,
    ) {
    }

    public function render(): string
    {
        return $this->definitions->render();
    }
}
