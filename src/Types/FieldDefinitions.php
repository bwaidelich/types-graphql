<?php

declare(strict_types=1);

namespace Wwwision\TypesGraphQL\Types;

use ArrayIterator;
use IteratorAggregate;
use Traversable;
use Webmozart\Assert\Assert;

/**
 * @implements IteratorAggregate<FieldDefinition>
 */
final class FieldDefinitions implements IteratorAggregate
{
    /**
     * @var FieldDefinition[]
     */
    private array $elements;

    public function __construct(FieldDefinition ...$elements)
    {
        Assert::notEmpty($elements, 'FieldDefinitions must not be empty');
        $this->elements = $elements;
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->elements);
    }
}
