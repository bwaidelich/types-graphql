<?php

declare(strict_types=1);

namespace Wwwision\TypesGraphQL\Types;

use Webmozart\Assert\Assert;

use function implode;

final class DirectiveLocations
{
    /**
     * @var DirectiveLocation[]
     */
    private array $elements;

    public function __construct(DirectiveLocation ...$elements)
    {
        Assert::notEmpty($elements, 'DirectiveLocations must not be empty');
        $this->elements = $elements;
    }

    public function render(): string
    {
        $renderedDefinitions = array_map(static fn (DirectiveLocation $directiveLocation) => $directiveLocation->name, $this->elements);
        return implode(' | ', $renderedDefinitions);
    }
}
