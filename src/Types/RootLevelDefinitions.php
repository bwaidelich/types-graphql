<?php

declare(strict_types=1);

namespace Wwwision\TypesGraphQL\Types;

use Webmozart\Assert\Assert;

use function implode;

final class RootLevelDefinitions
{
    /**
     * @var RootLevelDefinition[]
     */
    private array $elements;

    public function __construct(RootLevelDefinition ...$elements)
    {
        Assert::notEmpty($elements, 'RootLevelDefinitions must not be empty');
        $this->elements = $elements;
    }

    public function render(): string
    {
        $renderedDefinitions = array_map(static fn (RootLevelDefinition $definition) => $definition->render(), $this->elements);
        return implode("\n", $renderedDefinitions);
    }
}
