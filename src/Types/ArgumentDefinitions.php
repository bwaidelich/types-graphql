<?php

declare(strict_types=1);

namespace Wwwision\TypesGraphQL\Types;

use Webmozart\Assert\Assert;

use function implode;

final class ArgumentDefinitions
{
    /**
     * @var ArgumentDefinition[]
     */
    private array $elements;

    public function __construct(ArgumentDefinition ...$elements)
    {
        Assert::notEmpty($elements, 'ArgumentDefinitions must not be empty');
        $this->elements = $elements;
    }

    public function render(): string
    {
        $renderedDefinitions = array_map(static fn (ArgumentDefinition $definition) => $definition->render(), $this->elements);
        return implode(' ', $renderedDefinitions);
    }
}
