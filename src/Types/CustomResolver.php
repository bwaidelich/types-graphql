<?php

declare(strict_types=1);

namespace Wwwision\TypesGraphQL\Types;

use Closure;

final class CustomResolver
{
    public function __construct(
        public readonly string $typeName,
        public readonly string $fieldName,
        public readonly Closure $callback,
        public readonly ?string $description = null,
    ) {
    }
}
