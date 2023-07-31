<?php

declare(strict_types=1);

namespace Wwwision\TypesGraphQL\Types;

use Webmozart\Assert\Assert;

use function array_filter;
use function array_map;
use function implode;

final class Directives
{
    /**
     * @var Directive[]
     */
    private array $elements;

    public function __construct(Directive ...$elements)
    {
        Assert::notEmpty($elements, 'Directives must not be empty');
        $this->elements = $elements;
    }

    public function render(): string
    {
        $renderedDirectives = array_map(static fn (Directive $directive) => $directive->render(), $this->elements);
        return implode(' ', $renderedDirectives);
    }

    public function getDescription(): string
    {
        $renderedDescriptions = array_map(static fn (Directive $directive) => $directive->description, array_filter($this->elements, static fn (Directive $directive) => $directive->description !== null));
        return implode("\n", $renderedDescriptions);
    }
}
