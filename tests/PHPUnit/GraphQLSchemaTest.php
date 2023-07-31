<?php
declare(strict_types=1);

namespace Wwwision\TypesGraphQL\Tests\PHPUnit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Wwwision\TypesGraphQL\GraphQLGenerator;
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
use Wwwision\TypesGraphQL\Types\ObjectTypeDefinition;
use Wwwision\TypesGraphQL\Types\RootLevelDefinitions;
use Wwwision\TypesGraphQL\Types\ScalarTypeDefinition;
use Wwwision\TypesGraphQL\GraphQLSchema;

#[CoversClass(GraphQLGenerator::class)]
#[CoversClass(GraphQLSchema::class)]
#[CoversClass(Argument::class)]
#[CoversClass(Arguments::class)]
#[CoversClass(Directive::class)]
#[CoversClass(DirectiveDefinition::class)]
#[CoversClass(DirectiveLocations::class)]
#[CoversClass(Directives::class)]
#[CoversClass(ObjectTypeDefinition::class)]
#[CoversClass(RootLevelDefinitions::class)]
#[CoversClass(ArgumentValue::class)]
#[CoversClass(ArgumentDefinition::class)]
#[CoversClass(ArgumentDefinitions::class)]
#[CoversClass(FieldDefinitions::class)]
#[CoversClass(FieldDefinition::class)]
#[CoversClass(FieldType::class)]
#[CoversClass(ScalarTypeDefinition::class)]
#[CoversClass(EnumTypeDefinition::class)]
#[CoversClass(EnumValueDefinition::class)]
#[CoversClass(EnumValueDefinitions::class)]
final class GraphQLSchemaTest extends TestCase
{

    public function test(): void
    {
        $queryDefinition = new ObjectTypeDefinition(name: 'Query', fieldDefinitions: new FieldDefinitions(new FieldDefinition(name: 'hero', type: new FieldType('Character'), argumentDefinitions: new ArgumentDefinitions(new ArgumentDefinition(description: 'Some arg description', name: 'episode', type: new FieldType('Episode'), defaultValue: new ArgumentValue(new class { function __toString() { return 'EMPIRE';}}))))));
        $episodeDefinition = new EnumTypeDefinition(name: 'Episode', valueDefinitions: new EnumValueDefinitions(new EnumValueDefinition('NEWHOPE'), new EnumValueDefinition('EMPIRE'), new EnumValueDefinition('JEDI')), directives: new Directives(new Directive('someEnumDirective')));
        $characterDefinition = new ObjectTypeDefinition(name: 'Character', fieldDefinitions: new FieldDefinitions(new FieldDefinition(name: 'name', type: new FieldType('String', true)), new FieldDefinition(name: 'appearsIn', type: new FieldType('[Episode]', true))), directives: new Directives(new Directive('someDirective')));
        $rootLevelDefinitions = new RootLevelDefinitions($queryDefinition, $episodeDefinition, $characterDefinition);

        $schema = new GraphQLSchema($rootLevelDefinitions);

        $expected = <<<GRAPHQL
            type Query {
              hero(""" Some arg description """ episode: Episode = EMPIRE): Character
            }

            enum Episode @someEnumDirective {
              NEWHOPE
              EMPIRE
              JEDI
            }

            type Character @someDirective {
              name: String!
              appearsIn: [Episode]!
            }

            GRAPHQL;
        self::assertSame($expected, $schema->render());
    }

}
