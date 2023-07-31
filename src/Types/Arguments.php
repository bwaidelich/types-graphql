<?php

declare(strict_types=1);

namespace Wwwision\TypesGraphQL\Types;

use Webmozart\Assert\Assert;

use function implode;

final class Arguments
{
    /**
     * @var Argument[]
     */
    private array $elements;

    public function __construct(Argument ...$elements)
    {
        Assert::notEmpty($elements, 'Arguments must not be empty');
        $this->elements = $elements;
    }

    public function render(): string
    {
        return implode(' ', array_map(static fn (Argument $argument) => $argument->render(), $this->elements));
    }
}
