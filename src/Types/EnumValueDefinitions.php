<?php

declare(strict_types=1);

namespace Wwwision\TypesGraphQL\Types;

use Webmozart\Assert\Assert;

use function implode;

final class EnumValueDefinitions
{
    /**
     * @var EnumValueDefinition[]
     */
    private array $elements;

    public function __construct(EnumValueDefinition ...$elements)
    {
        Assert::notEmpty($elements, 'EnumValueDefinitions must not be empty');
        $this->elements = $elements;
    }

    public function render(): string
    {
        $renderedDefinitions = array_map(static fn (EnumValueDefinition $definition) => str_replace("\n", "\n  ", $definition->render()), $this->elements);
        return "  " . implode("\n  ", $renderedDefinitions) . "\n";
    }
}
