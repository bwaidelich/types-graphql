<?php
declare(strict_types=1);

namespace Wwwision\TypesGraphQL\Tests\PHPUnit\Types;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Wwwision\TypesGraphQL\Types\ArgumentDefinition;
use Wwwision\TypesGraphQL\Types\ArgumentDefinitions;
use Wwwision\TypesGraphQL\Types\Directive;
use Wwwision\TypesGraphQL\Types\DirectiveDefinition;
use Wwwision\TypesGraphQL\Types\DirectiveLocation;
use Wwwision\TypesGraphQL\Types\DirectiveLocations;
use Wwwision\TypesGraphQL\Types\Directives;
use Wwwision\TypesGraphQL\Types\FieldType;
use Wwwision\TypesGraphQL\Types\ScalarTypeDefinition;

#[CoversClass(DirectiveDefinition::class)]
#[CoversClass(DirectiveLocations::class)]
#[CoversClass(ArgumentDefinition::class)]
#[CoversClass(ArgumentDefinitions::class)]
#[CoversClass(FieldType::class)]
final class DirectiveDefinitionTest extends TestCase
{

    public function test_simple(): void
    {
        $definition = new DirectiveDefinition(name: 'SomeDirective', locations: new DirectiveLocations(DirectiveLocation::FRAGMENT_DEFINITION, DirectiveLocation::INTERFACE));

        $expected = <<<GRAPHQL
            directive @SomeDirective on FRAGMENT_DEFINITION | INTERFACE


            GRAPHQL;
        self::assertSame($expected, $definition->render());
    }

    public function test_single_argument(): void
    {
        $definition = new DirectiveDefinition(name: 'SomeDirective', locations: new DirectiveLocations(DirectiveLocation::FRAGMENT_DEFINITION, DirectiveLocation::INTERFACE), argumentDefinitions: new ArgumentDefinitions(new ArgumentDefinition('minLength', new FieldType('Int'))));

        $expected = <<<GRAPHQL
            directive @SomeDirective(minLength: Int) on FRAGMENT_DEFINITION | INTERFACE


            GRAPHQL;
        self::assertSame($expected, $definition->render());
    }

    public function test_complex(): void
    {
        $definition = new DirectiveDefinition(name: 'constraint', locations: new DirectiveLocations(DirectiveLocation::FIELD_DEFINITION, DirectiveLocation::SCALAR), argumentDefinitions: new ArgumentDefinitions(new ArgumentDefinition('minLength', new FieldType('Int')), new ArgumentDefinition('maxLength', new FieldType('Int'))), description: 'Some directive description');

        $expected = <<<GRAPHQL
            """
            Some directive description
            """
            directive @constraint(minLength: Int maxLength: Int) on FIELD_DEFINITION | SCALAR


            GRAPHQL;
        self::assertSame($expected, $definition->render());
    }

}
