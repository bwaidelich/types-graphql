<?php
declare(strict_types=1);

namespace Wwwision\TypesGraphQL\Tests\PHPUnit;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;
use Wwwision\Types\Attributes\Description;
use Wwwision\Types\Attributes\IntegerBased;
use Wwwision\Types\Attributes\ListBased;
use Wwwision\Types\Attributes\StringBased;
use Wwwision\Types\Schema\StringTypeFormat;
use Wwwision\TypesGraphQL\Attributes\Mutation;
use Wwwision\TypesGraphQL\Attributes\Query;
use Wwwision\TypesGraphQL\GraphQLGenerator;
use Wwwision\TypesGraphQL\GraphQLSchema;
use Wwwision\TypesGraphQL\Types\Argument;
use Wwwision\TypesGraphQL\Types\ArgumentDefinition;
use Wwwision\TypesGraphQL\Types\ArgumentDefinitions;
use Wwwision\TypesGraphQL\Types\Arguments;
use Wwwision\TypesGraphQL\Types\ArgumentValue;
use Wwwision\TypesGraphQL\Types\CustomResolver;
use Wwwision\TypesGraphQL\Types\CustomResolvers;
use Wwwision\TypesGraphQL\Types\Directive;
use Wwwision\TypesGraphQL\Types\DirectiveDefinition;
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
use Wwwision\TypesGraphQL\Types\RootLevelDefinitions;
use Wwwision\TypesGraphQL\Types\ScalarTypeDefinition;
use function Wwwision\Types\instantiate;

#[CoversClass(GraphQLGenerator::class)]
#[CoversClass(GraphQLSchema::class)]
#[CoversClass(ArgumentValue::class)]
#[CoversClass(Argument::class)]
#[CoversClass(Arguments::class)]
#[CoversClass(Directive::class)]
#[CoversClass(DirectiveDefinition::class)]
#[CoversClass(DirectiveLocations::class)]
#[CoversClass(Directives::class)]
#[CoversClass(EnumTypeDefinition::class)]
#[CoversClass(EnumValueDefinition::class)]
#[CoversClass(EnumValueDefinitions::class)]
#[CoversClass(ObjectTypeDefinition::class)]
#[CoversClass(RootLevelDefinitions::class)]
#[CoversClass(ArgumentDefinition::class)]
#[CoversClass(ArgumentDefinitions::class)]
#[CoversClass(FieldDefinitions::class)]
#[CoversClass(FieldDefinition::class)]
#[CoversClass(FieldType::class)]
#[CoversClass(ScalarTypeDefinition::class)]
#[CoversClass(InterfaceDefinition::class)]
#[CoversClass(CustomResolvers::class)]
#[CoversClass(CustomResolver::class)]
final class GraphQLGeneratorTest extends TestCase
{

    private GraphQLGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new GraphQLGenerator();
    }

    public function test_generate_throws_exception_if_specified_class_does_not_exist(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->generator->generate('NonExistingClass');
    }

    public function test_generate_throws_exception_if_specified_class_does_not_contain_query_or_mutation_methods(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->generator->generate(ClassWithoutQueriesAndMutations::class);
    }

    public function test_generate_throws_exception_if_specified_class_only_contains_mutation_methods(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->generator->generate(ClassWithoutQueries::class);
    }

    public function test_generate_throws_exception_if_specified_class_only_contains_non_public_query_methods(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->generator->generate(ClassWithNonPublicQuery::class);
    }

    public function test_generate_throws_exception_parameter_default_value_type_is_not_supported(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Failed to parse method "ping" of type "ClassWithInvalidQueryParameterDefaultValue": The default value of parameter "dateTimeImmutable" has to be of type bool, float, int, string or Stringable. Got: DateTimeImmutable');
        $this->generator->generate(ClassWithInvalidQueryParameterDefaultValue::class);
    }

    public function test_generate_throws_exception_for_class_without_fields(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing field definitions for "ClassWithoutPublicProperties"');
        $this->generator->generate(ClassWithQueryAndInvalidType::class)->render();
    }

    public function test_generate_throws_exception_for_class_with_recursive_types(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Recursive field definitions for "ClassWithRecursion"');
        $this->generator->generate(ClassWithQueryAndRecursiveType::class)->render();
    }

    public function test_complex_schema(): void
    {
        $graphQLSchema = $this->generator->generate(ClassWithQueriesAndMutations::class);

        $expected = <<<GRAPHQL
            """
            Custom constraint directive (see https://www.npmjs.com/package/graphql-constraint-directive)
            """
            directive @constraint(minLength: Int maxLength: Int pattern: String format: String min: Int max: Int minItems: Int maxItems: Int) on FIELD_DEFINITION | SCALAR | ARGUMENT_DEFINITION | INPUT_FIELD_DEFINITION
            
            
            type Query {
              """ Some query description """
              someQuery(numbers: [SomeNumber!] @constraint(minItems: 1 maxItems: 5)): Date!
              someOtherQuery(theTitle: Title!): Severity
              instruments: [Instrument!]!
            }
            
            type Mutation {
              """ Some mutation description """
              someMutation(shape: SomeShapeInput! optionalBool: Boolean = true): [SomeNumber!]
            }
            
            """
            Some number description
            
            *Constraints:*
            * Minimum value: `3`
            * Maximum value: `20`
            """
            scalar SomeNumber @constraint(min: 3 max: 20)
            
            """
            Some date description
            
            *Constraints:*
            * Format: `date`
            * Pattern: `\d{4}-\d{2}-\d{2}`
            * Minimum length: `3`
            * Maximum length: `10`
            """
            scalar Date @constraint(format: "date" pattern: "\\\d{4}-\\\d{2}-\\\d{2}" minLength: 3 maxLength: 10)
            
            enum Title {
              MR
              MRS
              OTHER
            }
            
            enum Severity {
              LOW
              MEDIUM
              HIGH
            }
            
            type Piano implements Instrument {
              name: String!
              keys: Int!
            }
            
            type Guitar implements Instrument {
              name: String!
              strings: Int!
            }
            
            """ Interface description """
            interface Instrument {
              """ Interface property description """
              name: String!
            }
            
            input SomeOtherShapeInput {
              title: Title!
            }

            input SomeShapeInput {
              """ Some number description """
              number: SomeNumber!
              numbers: [SomeNumber!]! @constraint(minItems: 1 maxItems: 5)
              boolean: Boolean
              int: Int
              string: String
              """ Some date description """
              date: Date
              nestedShape: SomeOtherShapeInput
            }

            GRAPHQL;
        self::assertSame($expected, $graphQLSchema->render());
    }

    public static function dataProvider_invalid_custom_resolvers(): iterable
    {
        yield 'missing first parameter' => ['customResolver' => new CustomResolver('SomeOtherShape', 'custom', fn (): bool => true), 'expectedException' => 'Custom resolver "custom" for type "SomeOtherShape" must expect an instance of SomeOtherShape as first argument, but the resolver has no arguments'];
        yield 'invalid first parameter type (simple type)' => ['customResolver' => new CustomResolver('SomeOtherShape', 'custom', fn (int $x): string => 'foo'), 'expectedException' => 'Custom resolver "custom" for type "SomeOtherShape" must expect an instance of SomeOtherShape as first argument, but the first argument is of type int'];
        yield 'invalid first parameter type (stdClass)' => ['customResolver' => new CustomResolver('SomeOtherShape', 'custom', fn (stdClass $title): string => $title->name), 'expectedException' => 'Failed to parse schema of first argument of custom resolver "custom" for type "SomeOtherShape": Missing constructor in class "stdClass"'];
        yield 'invalid first parameter type (nonexisting class)' => ['customResolver' => new CustomResolver('SomeOtherShape', 'custom', fn (\NonExistingClass $title): string => $title->name), 'expectedException' => 'Failed to parse schema of first argument of custom resolver "custom" for type "SomeOtherShape": Failed to get schema for class "NonExistingClass" because that class does not exist'];
        yield 'invalid first parameter type (existing class)' => ['customResolver' => new CustomResolver('SomeOtherShape', 'custom', fn (Title $title): string => $title->name), 'expectedException' => 'Custom resolver "custom" for type "SomeOtherShape" must expect an instance of SomeOtherShape as first argument, but the first argument is of type Title'];
        yield 'invalid first parameter type (union type)' => ['customResolver' => new CustomResolver('SomeOtherShape', 'custom', fn (string|int $title): int => 321), 'expectedException' => 'Custom resolver "custom" for type "SomeOtherShape" must expect an instance of SomeOtherShape as first argument, got ReflectionUnionType'];
        yield 'invalid 2nd parameter type (union type)' => ['customResolver' => new CustomResolver('SomeOtherShape', 'custom', fn (SomeOtherShape $shape, string|int $foo): int => 321), 'expectedException' => 'Failed to parse argument of custom resolver "custom" for type "SomeOtherShape": Expected an instance of ReflectionNamedType. Got: ReflectionUnionType'];
        yield 'missing return type' => ['customResolver' => new CustomResolver('SomeOtherShape', 'custom', fn (SomeOtherShape $shape) => 123), 'expectedException' => 'Return type of custom resolver "custom" for type "SomeOtherShape" is missing'];
        yield 'invalid return type (union type)' => ['customResolver' => new CustomResolver('SomeOtherShape', 'custom', fn (SomeOtherShape $shape): string|int => 123), 'expectedException' => 'Return type of custom resolver "custom" for type "SomeOtherShape" was expected to be of type ReflectionNamedType. Got: ReflectionUnionType'];
    }

    #[dataProvider('dataProvider_invalid_custom_resolvers')]
    public function test_invalid_custom_resolver(CustomResolver $customResolver, string $expectedException): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedException);
        $this->generator->generate(ClassWithQueries::class, CustomResolvers::create($customResolver));
    }

    public function test_custom_resolvers(): void
    {
        $customResolvers = CustomResolvers::create(
            new CustomResolver('SomeOtherShape', 'custom', fn (SomeOtherShape $shape): string => $shape->title->name, 'Some custom resolver description'),
            new CustomResolver('SomeOtherShape', 'customWithBoolArgument', fn (SomeOtherShape $x, #[Description('Some custom argument description')] bool $foo): bool => false),
            new CustomResolver('SomeOtherShape', 'customWithIntArgument', fn (SomeOtherShape $x, #[Description('Some custom argument description')] int $foo): bool => false),
            new CustomResolver('SomeOtherShape', 'customWithStringArgument', fn (SomeOtherShape $x, #[Description('Some custom argument description')] string $foo): bool => false),
            new CustomResolver('SomeOtherShape', 'customWithFloatArgument', fn (SomeOtherShape $x, #[Description('Some custom argument description')] float $foo): bool => false),
            new CustomResolver('SomeOtherShape', 'customWithObjectArguments', fn (SomeOtherShape $x, Title $title): Title => $title),
        );
        $graphQLSchema = $this->generator->generate(ClassWithQueries::class, $customResolvers);

        $expected = <<<GRAPHQL
            type Query {
              someQuery(in: SomeOtherShapeInput!): SomeOtherShape!
            }
            
            enum Title {
              MR
              MRS
              OTHER
            }
            
            input SomeOtherShapeInput {
              title: Title!
            }
            
            type SomeOtherShape {
              title: Title!
              """ Some custom resolver description """
              custom: String!
              customWithBoolArgument(foo: Boolean!): Boolean!
              customWithIntArgument(foo: Int!): Boolean!
              customWithStringArgument(foo: String!): Boolean!
              customWithFloatArgument(foo: Float!): Boolean!
              customWithObjectArguments(title: Title!): Title!
            }

            GRAPHQL;
        self::assertSame($expected, $graphQLSchema->render());
    }

}

final class ClassWithoutQueriesAndMutations {
    public function ping(string $in): string
    {
        return '...';
    }
}

final class ClassWithoutQueries {

    #[Mutation]
    public function ping(string $in): string
    {
        return '...';
    }
}

final class ClassWithNonPublicQuery {

    #[Query]
    protected function ping(string $in): string
    {
        return '...';
    }
}

final class ClassWithInvalidQueryParameterDefaultValue {

    #[Query]
    public function ping(\DateTimeImmutable $dateTimeImmutable = new \DateTimeImmutable()): string
    {
        return '...';
    }
}

final class ClassWithQueryAndInvalidType {
    #[Query]
    public function someQuery(SomeOtherShape $in): ClassInvalidProperty
    {
    }
}

final class ClassWithQueryAndInvalidInterfaceType {
    #[Query]
    public function someQuery(SomeOtherShape $in): ClassInvalidInterfaceProperty
    {
    }
}

final class ClassWithQueryAndRecursiveType {
    #[Query]
    public function someQuery(SomeOtherShape $in): ClassWithRecursion
    {
    }
}

final class ClassWithQueries {

    #[Query]
    public function someQuery(SomeOtherShape $in): SomeOtherShape
    {
    }
}

final class ClassWithQueriesAndMutations {

    #[Query]
    #[Description('Some query description')]
    public function someQuery(#[Description('some overridden number description (TODO: is ignored currently)')] SomeNumbers $numbers = null): Date
    {
    }

    #[Query]
    public function someOtherQuery(Title $theTitle): ?Severity
    {
    }

    #[Query]
    public function instruments(): Instruments
    {
        return instantiate(SomeNumbersOrStrings::class, [['__type' => SomeNumber::class, '__value' => 123], ['__type' => SomeString::class, '__value' => 'foo']]);
    }

    #[Mutation]
    #[Description('Some mutation description')]
    public function someMutation(SomeShape $shape, bool $optionalBool = true): ?SomeNumbers
    {
    }
}

#[IntegerBased(minimum: 3, maximum: 20)]
#[Description('Some number description')]
final class SomeNumber {
    private function __construct(public readonly int $value) {}
}

#[ListBased(itemClassName: SomeNumber::class, minCount: 1, maxCount: 5)]
final class SomeNumbers {
    private function __construct(private readonly array $numbers) {}
}

#[StringBased(minLength: 3, maxLength: 10, pattern: '\d{4}-\d{2}-\d{2}', format: StringTypeFormat::date)]
#[Description('Some date description')]
final class Date {
    private function __construct(public readonly string $value) {}
}

#[Description('Some shape description')]
final class SomeShape {
    public function __construct(
        public readonly SomeNumber $number,
        public readonly SomeNumbers $numbers,
        public readonly bool $boolean = false,
        public readonly ?int $int = null,
        public readonly ?string $string = null,
        public readonly ?Date $date = null,
        public readonly ?SomeOtherShape $nestedShape = null,
    ) {}
}

final class SomeOtherShape {
    public function __construct(
        public readonly Title $title,
    ) {}
}

enum Title {
    case MR;
    case MRS;
    case OTHER;
}

enum Severity: int {
    case LOW = 0;
    case MEDIUM = 1;
    case HIGH = 3;
}

#[Description('Interface description')]
interface Instrument {
    #[Description('Interface property description')]
    public function name(): string;
}

final class Piano implements Instrument {

    public function __construct(
        private readonly string $name,
        public readonly int $keys,
    ) {}

    public function name(): string {
        return $this->name;
    }
}

final class Guitar implements Instrument
{

    public function __construct(
        private readonly string $name,
        public readonly int $strings,
    ) {}

    public function name(): string {
        return $this->name;
    }
}


#[ListBased(itemClassName: Instrument::class, minCount: 0, maxCount: 5)]
final class Instruments
{
    private function __construct(private readonly array $instruments) {}
}

final class ClassInvalidProperty {
    public function __construct(
        ClassWithoutPublicProperties $invalidProperty,
    ) {}
}

final class ClassWithoutPublicProperties {
    public function __construct() {}
}

final class ClassWithRecursion {
    public function __construct(
        private readonly string $name,
        private readonly SubClassWithRecursion $subClass
    ) {}
}

final class SubClassWithRecursion {
    public function __construct(
        private readonly string $name,
        private readonly ClassWithRecursion $parentClass
    ) {}
}