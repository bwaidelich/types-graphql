<?php

declare(strict_types=1);

namespace Wwwision\TypesGraphQL\Types;

use ArrayIterator;
use InvalidArgumentException;
use IteratorAggregate;
use RecursiveArrayIterator;
use Traversable;

/**
 * @implements IteratorAggregate<CustomResolver>
 */
final class CustomResolvers implements IteratorAggregate
{
    /**
     * @param array<string, array<string, CustomResolver>> $resolversByTypeAndFieldName
     */
    private function __construct(
        private readonly array $resolversByTypeAndFieldName,
    ) {
    }

    public static function create(CustomResolver ...$resolvers): self
    {
        $instance = new self([]);
        foreach ($resolvers as $resolver) {
            $instance = $instance->with($resolver);
        }
        return $instance;
    }

    public function with(CustomResolver $resolver): self
    {
        if (array_key_exists($resolver->typeName, $this->resolversByTypeAndFieldName) && array_key_exists($resolver->fieldName, $this->resolversByTypeAndFieldName[$resolver->typeName])) {
            throw new InvalidArgumentException(sprintf('A resolver for field "%s" on type "%s" is already registered', $resolver->fieldName, $resolver->typeName), 1693480456);
        }
        return new self([...$this->resolversByTypeAndFieldName, ...[$resolver->typeName => [...$this->resolversByTypeAndFieldName[$resolver->typeName] ?? [], ...[$resolver->fieldName => $resolver]]]]);
    }

    /**
     * @return Traversable<CustomResolver>
     */
    public function getAllForType(string $typeName): Traversable
    {
        return new ArrayIterator(array_values($this->resolversByTypeAndFieldName[$typeName] ?? []));
    }

    public function get(string $typeName, string $fieldName): ?CustomResolver
    {
        return $this->resolversByTypeAndFieldName[$typeName][$fieldName] ?? null;
    }

    public function getIterator(): Traversable
    {
        foreach ($this->resolversByTypeAndFieldName as $resolvers) {
            foreach ($resolvers as $resolver) {
                yield $resolver;
            }
        }
    }
}
