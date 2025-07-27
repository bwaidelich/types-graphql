<?php

declare(strict_types=1);

namespace Wwwision\TypesGraphQL\Types;

use Closure;

final class DeferredRootLevelDefinition implements RootLevelDefinition
{
    private RootLevelDefinition|null $resolvedDefinition = null;

    /**
     * @param Closure(): RootLevelDefinition $definitionResolver
     */
    public function __construct(
        private readonly string $name,
        private readonly Closure $definitionResolver,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function render(): string
    {
        return $this->resolve()->render();
    }

    public function resolve(): RootLevelDefinition
    {
        if ($this->resolvedDefinition === null) {
            $this->resolvedDefinition = ($this->definitionResolver)();
        }
        return $this->resolvedDefinition;
    }
}
