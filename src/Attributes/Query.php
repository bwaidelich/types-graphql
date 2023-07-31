<?php

declare(strict_types=1);

namespace Wwwision\TypesGraphQL\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
final class Query extends GraphQLEndpoint
{
}
