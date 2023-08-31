<?php
declare(strict_types=1);

namespace Wwwision\TypesGraphQL\Tests\PHPUnit\Types;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Wwwision\TypesGraphQL\Types\Directive;
use Wwwision\TypesGraphQL\Types\Directives;
use Wwwision\TypesGraphQL\Types\EnumTypeDefinition;
use Wwwision\TypesGraphQL\Types\EnumValueDefinition;
use Wwwision\TypesGraphQL\Types\EnumValueDefinitions;
use Wwwision\TypesGraphQL\Types\FieldDefinition;
use Wwwision\TypesGraphQL\Types\FieldDefinitions;
use Wwwision\TypesGraphQL\Types\FieldType;
use Wwwision\TypesGraphQL\Types\InterfaceDefinition;
use Wwwision\TypesGraphQL\Types\ObjectTypeDefinition;
use Wwwision\TypesGraphQL\Types\ScalarTypeDefinition;

#[CoversClass(InterfaceDefinition::class)]
#[CoversClass(Directives::class)]
#[CoversClass(Directive::class)]
#[CoversClass(FieldDefinitions::class)]
#[CoversClass(FieldDefinition::class)]
#[CoversClass(FieldType::class)]
final class InterfaceDefinitionTest extends TestCase
{

    public function test_simple(): void
    {
        $definition = new InterfaceDefinition(name: 'SomeInterface', fieldDefinitions: new FieldDefinitions(new FieldDefinition(name: 'foo', type: new FieldType('String', true))), description: 'Some interface description', directives: new Directives(new Directive(name: 'foo'), new Directive(name: 'bar')));

        $expected = <<<GRAPHQL
            """ Some interface description """
            interface SomeInterface @foo @bar {
              foo: String!
            }

            GRAPHQL;
        self::assertSame($expected, $definition->render());
    }

}
