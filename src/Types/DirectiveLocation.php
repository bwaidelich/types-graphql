<?php

declare(strict_types=1);

namespace Wwwision\TypesGraphQL\Types;

enum DirectiveLocation
{
    case QUERY;
    case MUTATION;
    case SUBSCRIPTION;
    case FIELD;
    case FRAGMENT_DEFINITION;
    case FRAGMENT_SPREAD;
    case INLINE_FRAGMENT;
    case VARIABLE_DEFINITION;
    case SCHEMA;
    case SCALAR;
    case OBJECT;
    case FIELD_DEFINITION;
    case ARGUMENT_DEFINITION;
    case INTERFACE;
    case UNION;
    case ENUM;
    case ENUM_VALUE;
    case INPUT_OBJECT;
    case INPUT_FIELD_DEFINITION;
}
