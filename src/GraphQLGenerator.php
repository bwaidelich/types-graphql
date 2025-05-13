<?php

declare(strict_types=1);

namespace Wwwision\TypesGraphQL;

use InvalidArgumentException;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use RuntimeException;
use Stringable;
use Webmozart\Assert\Assert;
use Wwwision\Types\Attributes\Description;
use Wwwision\Types\Parser;
use Wwwision\Types\Schema\EnumSchema;
use Wwwision\Types\Schema\IntegerSchema;
use Wwwision\Types\Schema\InterfaceSchema;
use Wwwision\Types\Schema\ListSchema;
use Wwwision\Types\Schema\LiteralBooleanSchema;
use Wwwision\Types\Schema\LiteralFloatSchema;
use Wwwision\Types\Schema\LiteralIntegerSchema;
use Wwwision\Types\Schema\LiteralStringSchema;
use Wwwision\Types\Schema\OptionalSchema;
use Wwwision\Types\Schema\Schema;
use Wwwision\Types\Schema\ShapeSchema;
use Wwwision\Types\Schema\StringSchema;
use Wwwision\Types\Schema\StringTypeFormat;
use Wwwision\TypesGraphQL\Attributes\GraphQLEndpoint;
use Wwwision\TypesGraphQL\Attributes\Query;
use Wwwision\TypesGraphQL\Types\Argument;
use Wwwision\TypesGraphQL\Types\ArgumentDefinition;
use Wwwision\TypesGraphQL\Types\ArgumentDefinitions;
use Wwwision\TypesGraphQL\Types\Arguments;
use Wwwision\TypesGraphQL\Types\ArgumentValue;
use Wwwision\TypesGraphQL\Types\CustomResolvers;
use Wwwision\TypesGraphQL\Types\Directive;
use Wwwision\TypesGraphQL\Types\DirectiveDefinition;
use Wwwision\TypesGraphQL\Types\DirectiveLocation;
use Wwwision\TypesGraphQL\Types\DirectiveLocations;
use Wwwision\TypesGraphQL\Types\Directives;
use Wwwision\TypesGraphQL\Types\EnumTypeDefinition;
use Wwwision\TypesGraphQL\Types\EnumValueDefinition;
use Wwwision\TypesGraphQL\Types\EnumValueDefinitions;
use Wwwision\TypesGraphQL\Types\FieldDefinition;
use Wwwision\TypesGraphQL\Types\FieldDefinitions;
use Wwwision\TypesGraphQL\Types\FieldType;
use Wwwision\TypesGraphQL\Types\InterfaceDefinition;
use Wwwision\TypesGraphQL\Types\ObjectTypeDefinition;
use Wwwision\TypesGraphQL\Types\RootLevelDefinition;
use Wwwision\TypesGraphQL\Types\RootLevelDefinitions;
use Wwwision\TypesGraphQL\Types\ScalarTypeDefinition;

use function array_key_exists;
use function sprintf;

final class GraphQLGenerator
{
    /**
     * @var array<string, RootLevelDefinition>
     */
    private array $createdDefinitions = [];

    private CustomResolvers $customResolvers;

    private bool $constraintDirectivesWhereAdded = false;

    public function __construct()
    {
    }

    /**
     * @param class-string $className
     */
    public function generate(string $className, CustomResolvers|null $customResolvers = null): GraphQLSchema
    {
        $this->createdDefinitions = [];
        $this->customResolvers = $customResolvers ?? CustomResolvers::create();
        $this->constraintDirectivesWhereAdded = false;
        Assert::classExists($className);
        $reflectionClass = new ReflectionClass($className);
        $reflectionMethods = $reflectionClass->getMethods(ReflectionMethod::IS_PUBLIC);

        $queryFieldDefinitions = [];
        $mutationFieldDefinitions = [];

        foreach ($reflectionMethods as $reflectionMethod) {
            $graphQLEndpointAttribute = $reflectionMethod->getAttributes(GraphQLEndpoint::class, ReflectionAttribute::IS_INSTANCEOF)[0] ?? null;
            if ($graphQLEndpointAttribute === null) {
                continue;
            }
            $returnType = $reflectionMethod->getReturnType();
            Assert::notNull($returnType, sprintf('Return type of method "%s" is missing', $reflectionMethod->getName()));
            Assert::isInstanceOf($returnType, ReflectionNamedType::class, sprintf('Return type of method "%s" was expected to be of type %%2$s. Got: %%s', $reflectionMethod->getName()));

            try {
                $argumentDefinitions = $this->argumentDefinitionsFromReflectionParameters($reflectionMethod->getParameters());
            } catch (InvalidArgumentException $exception) {
                throw new InvalidArgumentException(sprintf('Failed to parse method "%s" of type "%s": %s', $reflectionMethod->getName(), $reflectionClass->getShortName(), $exception->getMessage()), 1693477914, $exception);
            }
            $fieldDefinition = new FieldDefinition(
                name: $reflectionMethod->getName(),
                type: $this->fieldTypeFromReflectionReturnType($returnType),
                description: self::getMethodDescription($reflectionMethod),
                argumentDefinitions: $argumentDefinitions,
            );
            if ($graphQLEndpointAttribute->getName() === Query::class) {
                $queryFieldDefinitions[] = $fieldDefinition;
            } else {
                $mutationFieldDefinitions[] = $fieldDefinition;
            }
        }
        if ($queryFieldDefinitions === []) {
            throw new InvalidArgumentException(sprintf('The provided class "%s" contains no public method with the %s attribute, but at least one query is required according to the GraphQL specification', $className, Query::class), 1689183791);
        }
        // @phpstan-ignore-next-line – for some reason PHPStan thinks that this condition is always false, but it is not
        if ($this->constraintDirectivesWhereAdded) {
            $rootLevelDefinitions['constraint'] = new DirectiveDefinition(
                name: 'constraint',
                locations: new DirectiveLocations(DirectiveLocation::FIELD_DEFINITION, DirectiveLocation::SCALAR, DirectiveLocation::ARGUMENT_DEFINITION, DirectiveLocation::INPUT_FIELD_DEFINITION),
                argumentDefinitions: new ArgumentDefinitions(
                    new ArgumentDefinition('minLength', new FieldType('Int')),
                    new ArgumentDefinition('maxLength', new FieldType('Int')),
                    new ArgumentDefinition('pattern', new FieldType('String')),
                    new ArgumentDefinition('format', new FieldType('String')),
                    new ArgumentDefinition('min', new FieldType('Int')),
                    new ArgumentDefinition('max', new FieldType('Int')),
                    new ArgumentDefinition('minItems', new FieldType('Int')),
                    new ArgumentDefinition('maxItems', new FieldType('Int')),
                ),
                description: 'Custom constraint directive (see https://www.npmjs.com/package/graphql-constraint-directive)',
            );
        }
        $rootLevelDefinitions['Query'] = new ObjectTypeDefinition(
            name: 'Query',
            fieldDefinitions: new FieldDefinitions(...$queryFieldDefinitions),
            directives: null,
            isInputType: false
        );
        if ($mutationFieldDefinitions !== []) {
            $rootLevelDefinitions['Mutation'] = new ObjectTypeDefinition(
                name: 'Mutation',
                fieldDefinitions: new FieldDefinitions(...$mutationFieldDefinitions),
                directives: null,
                isInputType: false
            );
        }
        $rootLevelDefinitions = [...$rootLevelDefinitions, ...$this->createdDefinitions];
        return new GraphQLSchema(new RootLevelDefinitions(...$rootLevelDefinitions));
    }

    private function fieldTypeFromReflectionReturnType(ReflectionNamedType $reflectionReturnType): FieldType
    {
        $returnTypeSchema = self::reflectionTypeToSchema($reflectionReturnType);
        $returnTypeDefinition = $this->typeDefinition($returnTypeSchema, false);
        return new FieldType($returnTypeDefinition->getName(), !$reflectionReturnType->allowsNull(), $returnTypeSchema instanceof ListSchema);
    }

    /**
     * @param array<ReflectionParameter> $reflectionParameters
     * @return ArgumentDefinitions|null
     */
    private function argumentDefinitionsFromReflectionParameters(array $reflectionParameters): ?ArgumentDefinitions
    {
        $argumentDefinitions = [];
        foreach ($reflectionParameters as $reflectionParameter) {
            $parameterReflectionType = $reflectionParameter->getType();
            Assert::isInstanceOf($parameterReflectionType, ReflectionNamedType::class);
            $parameterSchema = self::reflectionTypeToSchema($parameterReflectionType);
            $argumentTypeDefinition = $this->typeDefinition($parameterSchema, true);

            $argumentDefaultValue = null;
            if ($reflectionParameter->isDefaultValueAvailable() && $reflectionParameter->getDefaultValue() !== null) {
                $argumentDefaultValue = $reflectionParameter->getDefaultValue();
                if (!is_bool($argumentDefaultValue) && !is_float($argumentDefaultValue) && !is_int($argumentDefaultValue) && !is_string($argumentDefaultValue)) {
                    Assert::isInstanceOf($argumentDefaultValue, Stringable::class, sprintf('The default value of parameter "%s" has to be of type bool, float, int, string or Stringable. Got: %%s', $reflectionParameter->getName()));
                }
            }
            $argumentDefinitions[] = new ArgumentDefinition(
                name: $reflectionParameter->getName(),
                type: new FieldType($argumentTypeDefinition->getName(), !$reflectionParameter->isOptional(), $parameterSchema instanceof ListSchema),
                defaultValue: $argumentDefaultValue !== null ? new ArgumentValue($argumentDefaultValue) : null,
                directives: $this->directives($parameterSchema),
            );
        }
        if ($argumentDefinitions === []) {
            return null;
        }
        return new ArgumentDefinitions(...$argumentDefinitions);
    }

    private static function reflectionTypeToSchema(ReflectionNamedType $reflectionType): Schema
    {
        if ($reflectionType->isBuiltin()) {
            return match ($reflectionType->getName()) {
                'bool' => new LiteralBooleanSchema(null),
                'int' => new LiteralIntegerSchema(null),
                'string' => new LiteralStringSchema(null),
                'float' => new LiteralFloatSchema(null),
                default => throw new InvalidArgumentException(sprintf('No support for type %s', $reflectionType->getName())),
            };
        }
        $typeClassName = $reflectionType->getName();
        Assert::classExists($typeClassName);
        return Parser::getSchema($typeClassName);
    }

    private function typeDefinition(Schema $schema, bool $isInputType): RootLevelDefinition
    {
        if ($schema instanceof ListSchema) {
            $schema = $schema->itemSchema;
        }
        $cacheId = $schema->getName() . ($schema instanceof ShapeSchema && $isInputType ? 'Input' : '');
        $definition = match ($schema::class) {
            EnumSchema::class => $this->enumDefinition($schema),
            IntegerSchema::class,
            StringSchema::class,
            LiteralBooleanSchema::class,
            LiteralIntegerSchema::class,
            LiteralStringSchema::class,
            LiteralFloatSchema::class => $this->scalarDefinition($schema),
            ShapeSchema::class => $this->shapeDefinition($schema, $isInputType),
            InterfaceSchema::class => $this->interfaceDefinition($schema),
            default => throw new RuntimeException(sprintf('Unsupported schema "%s" for type "%s"', get_debug_type($schema), $schema->getName()))
        };
        if (!array_key_exists($cacheId, $this->createdDefinitions) && !in_array($schema::class, [LiteralBooleanSchema::class, LiteralIntegerSchema::class, LiteralStringSchema::class, LiteralFloatSchema::class], true)) {
            $this->createdDefinitions[$cacheId] = $definition;
        }
        return $definition;
    }

    private function scalarDefinition(IntegerSchema|StringSchema|LiteralBooleanSchema|LiteralIntegerSchema|LiteralStringSchema|LiteralFloatSchema $schema): ScalarTypeDefinition
    {
        $name = match ($schema::class) {
            LiteralBooleanSchema::class,
            LiteralIntegerSchema::class,
            LiteralStringSchema::class,
            LiteralFloatSchema::class => $this->literalTypeName($schema),
            default => $schema->getName(),
        };
        return new ScalarTypeDefinition(name: $name, description: $schema->getDescription(), directives: $this->directives($schema));
    }

    private function literalTypeName(LiteralBooleanSchema|LiteralIntegerSchema|LiteralStringSchema|LiteralFloatSchema $schema): string
    {
        return match ($schema::class) {
            LiteralBooleanSchema::class => 'Boolean',
            LiteralIntegerSchema::class => 'Int',
            LiteralStringSchema::class => 'String',
            LiteralFloatSchema::class => 'Float',
        };
    }

    private function enumDefinition(EnumSchema $schema): EnumTypeDefinition
    {
        $valueDefinitions = [];
        foreach ($schema->caseSchemas as $caseSchema) {
            $valueDefinitions[] = new EnumValueDefinition(
                name: $caseSchema->getName(),
                description: $caseSchema->getDescription(),
            );
        }
        return new EnumTypeDefinition(
            name: $schema->getName(),
            valueDefinitions: new EnumValueDefinitions(...$valueDefinitions),
            description: $schema->getDescription(),
        );
    }

    private function shapeDefinition(ShapeSchema $schema, bool $isInputType): ObjectTypeDefinition
    {
        $typeName = $schema->getName() . ($isInputType ? 'Input' : '');
        $fieldDefinitions = $this->propertyFieldDefinitions($schema, $isInputType);
        foreach ($this->customResolvers->getAllForType($typeName) as $customResolver) {
            $customResolverReflection = new ReflectionFunction($customResolver->callback);
            $customResolverParameters = $customResolverReflection->getParameters();
            Assert::keyExists($customResolverParameters, 0, sprintf('Custom resolver "%s" for type "%s" must expect an instance of %s as first argument, but the resolver has no arguments', $customResolver->fieldName, $customResolver->typeName, $schema->getName()));
            $customResolverFirstParameterReflectionType = $customResolverParameters[0]->getType();
            Assert::isInstanceOf($customResolverFirstParameterReflectionType, ReflectionNamedType::class, sprintf('Custom resolver "%s" for type "%s" must expect an instance of %s as first argument, got %%s', $customResolver->fieldName, $customResolver->typeName, $schema->getName()));
            if ($customResolverFirstParameterReflectionType->isBuiltin()) {
                throw new InvalidArgumentException(sprintf('Custom resolver "%s" for type "%s" must expect an instance of %s as first argument, but the first argument is of type %s', $customResolver->fieldName, $customResolver->typeName, $schema->getName(), $customResolverFirstParameterReflectionType->getName()), 1693474790);
            }
            /** @var class-string $customResolverFirstParameterClassName */
            $customResolverFirstParameterClassName = $customResolverFirstParameterReflectionType->getName();
            try {
                $customResolverFirstParameterSchema = Parser::getSchema($customResolverFirstParameterClassName);
            } catch (InvalidArgumentException $exception) {
                throw new InvalidArgumentException(sprintf('Failed to parse schema of first argument of custom resolver "%s" for type "%s": %s', $customResolver->fieldName, $customResolver->typeName, $exception->getMessage()), 1693475021);
            }
            Assert::same($customResolverFirstParameterSchema->getName(), $schema->getName(), sprintf('Custom resolver "%s" for type "%s" must expect an instance of %s as first argument, but the first argument is of type %s', $customResolver->fieldName, $customResolver->typeName, $schema->getName(), $customResolverFirstParameterSchema->getName()));
            array_shift($customResolverParameters);
            $customResolverReturnType = $customResolverReflection->getReturnType();
            Assert::notNull($customResolverReturnType, sprintf('Return type of custom resolver "%s" for type "%s" is missing', $customResolver->fieldName, $customResolver->typeName));
            Assert::isInstanceOf($customResolverReturnType, ReflectionNamedType::class, sprintf('Return type of custom resolver "%s" for type "%s" was expected to be of type %%2$s. Got: %%s', $customResolver->fieldName, $customResolver->typeName));
            try {
                $customResolverArgumentDefinitions = $this->argumentDefinitionsFromReflectionParameters($customResolverParameters);
            } catch (InvalidArgumentException $exception) {
                throw new InvalidArgumentException(sprintf('Failed to parse argument of custom resolver "%s" for type "%s": %s', $customResolver->fieldName, $customResolver->typeName, $exception->getMessage()), 1693477576);
            }
            $fieldDefinitions[] = new FieldDefinition(
                name: $customResolver->fieldName,
                type: $this->fieldTypeFromReflectionReturnType($customResolverReturnType),
                description: $customResolver->description,
                argumentDefinitions: $customResolverArgumentDefinitions,
            );
        }
        if ($fieldDefinitions === []) {
            throw new InvalidArgumentException(sprintf('Missing field definitions for "%s"', $schema->getName()));
        }
        return new ObjectTypeDefinition(
            name: $typeName,
            fieldDefinitions: new FieldDefinitions(...$fieldDefinitions),
            isInputType: $isInputType
        );
    }

    private function interfaceDefinition(InterfaceSchema $schema): InterfaceDefinition
    {
        foreach ($schema->implementationSchemas() as $implementationSchema) {
            $implementationDefinition = $this->typeDefinition($implementationSchema, false);
            Assert::isInstanceOf($implementationDefinition, ObjectTypeDefinition::class);
            $implementationDefinition->implementsInterface($schema->getName());
        }
        $fieldDefinitions = $this->propertyFieldDefinitions($schema, false);
        return new InterfaceDefinition(
            name: $schema->getName(),
            fieldDefinitions: new FieldDefinitions(...$fieldDefinitions),
            description: $schema->description,
        );
    }

    /**
     * @return array<FieldDefinition>
     */
    private function propertyFieldDefinitions(ShapeSchema|InterfaceSchema $schema, bool $isInputType): array
    {
        $fieldDefinitions = [];
        foreach ($schema->propertySchemas as $propertyName => $propertySchema) {
            $required = true;
            if ($propertySchema instanceof OptionalSchema) {
                $propertySchema = $propertySchema->wrapped;
                $required = false;
            }
            if ($propertySchema instanceof LiteralBooleanSchema || $propertySchema instanceof LiteralIntegerSchema || $propertySchema instanceof LiteralStringSchema) {
                $propertyFieldType = new FieldType($this->literalTypeName($propertySchema), $required);
            } elseif ($propertySchema instanceof ListSchema) {
                $propertyTypeDefinition = $this->typeDefinition($propertySchema->itemSchema, $isInputType);
                $propertyFieldType = new FieldType($propertyTypeDefinition->getName(), $required, true, $this->directives($propertySchema));
            } elseif ($propertySchema instanceof ShapeSchema && $isInputType) {
                $this->typeDefinition($propertySchema, true);
                $propertyFieldType = new FieldType($propertySchema->getName() . 'Input', $required);
            } else {
                $this->typeDefinition($propertySchema, $isInputType);
                $propertyFieldType = new FieldType($propertySchema->getName(), $required);
            }

            $overriddenDescription = $schema->overriddenPropertyDescription($propertyName);
            $fieldDefinitions[] = new FieldDefinition(
                name: $propertyName,
                type: $propertyFieldType,
                description: $overriddenDescription ?? $propertySchema->getDescription(),
                argumentDefinitions: null // TODO support argument fields?
            );
        }
        return $fieldDefinitions;
    }

    private function directives(Schema $schema): ?Directives
    {
        $constraintDescription = "*Constraints:*\n";
        $constraintDirectiveArguments = match ($schema::class) {
            StringSchema::class => $this->stringConstraintDirectiveArguments($schema, $constraintDescription),
            IntegerSchema::class => $this->intConstraintDirectiveArguments($schema, $constraintDescription),
            ListSchema::class => $this->listConstraintDirectiveArguments($schema, $constraintDescription),
            default => [],
        };
        if ($constraintDirectiveArguments === []) {
            return null;
        }
        $this->constraintDirectivesWhereAdded = true;
        return new Directives(new Directive(name: 'constraint', description: $constraintDescription, arguments: new Arguments(...$constraintDirectiveArguments)));
    }

    /**
     * @return array<Argument>
     */
    private function stringConstraintDirectiveArguments(StringSchema $schema, string &$description): array
    {
        $arguments = [];
        if ($schema->format !== null) {
            $arguments[] = new Argument(name: 'format', value: new ArgumentValue($schema->format->name));
            $description .= "* Format: `{$schema->format->name}`\n";
        }
        if ($schema->pattern !== null) {
            $arguments[] = new Argument(name: 'pattern', value: new ArgumentValue($schema->pattern));
            $description .= "* Pattern: `$schema->pattern`\n";
        }
        if ($schema->minLength !== null) {
            $arguments[] = new Argument(name: 'minLength', value: new ArgumentValue($schema->minLength));
            $description .= "* Minimum length: `$schema->minLength`\n";
        }
        if ($schema->maxLength !== null) {
            $arguments[] = new Argument(name: 'maxLength', value: new ArgumentValue($schema->maxLength));
            $description .= "* Maximum length: `$schema->maxLength`\n";
        }
        return $arguments;
    }

    /**
     * @return array<Argument>
     */
    private function intConstraintDirectiveArguments(IntegerSchema $schema, string &$description): array
    {
        $arguments = [];
        if ($schema->minimum !== null) {
            $arguments[] = new Argument(name: 'min', value: new ArgumentValue($schema->minimum));
            $description .= "* Minimum value: `$schema->minimum`\n";
        }
        if ($schema->maximum !== null) {
            $arguments[] = new Argument(name: 'max', value: new ArgumentValue($schema->maximum));
            $description .= "* Maximum value: `$schema->maximum`\n";
        }
        return $arguments;
    }

    /**
     * @return array<Argument>
     */
    private function listConstraintDirectiveArguments(ListSchema $schema, string &$description): array
    {
        $arguments = [];
        if ($schema->minCount !== null) {
            $arguments[] = new Argument(name: 'minItems', value: new ArgumentValue($schema->minCount));
            $description .= "* Minimum number of items: `$schema->minCount`\n";
        }
        if ($schema->maxCount !== null) {
            $arguments[] = new Argument(name: 'maxItems', value: new ArgumentValue($schema->maxCount));
            $description .= "* Maximum number of items: `$schema->maxCount`\n";
        }
        return $arguments;
    }

    private static function getMethodDescription(ReflectionMethod $reflection): ?string
    {
        $descriptionAttributes = $reflection->getAttributes(Description::class, ReflectionAttribute::IS_INSTANCEOF);
        if (!isset($descriptionAttributes[0])) {
            return null;
        }
        /** @var Description $instance */
        $instance = $descriptionAttributes[0]->newInstance();
        return $instance->value;
    }
}
