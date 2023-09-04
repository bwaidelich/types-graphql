<?php
declare(strict_types=1);

namespace Wwwision\TypesGraphQL\Tests\PHPUnit;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
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
        $this->expectExceptionMessage('The default value of parameter "dateTimeImmutable" for method "ping" has to be of type bool, float, int, string or Stringable. Got: DateTimeImmutable');
        $this->generator->generate(ClassWithInvalidQueryParameterDefaultValue::class);
    }

    public function test_generate_throws_exception_for_class_without_fields(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing field definitions for "ClassWithoutPublicProperties"');
        $this->generator->generate(ClassWithQueryAndInvalidType::class)->render();
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