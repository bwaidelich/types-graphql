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
use Wwwision\TypesGraphQL\Types\ScalarTypeDefinition;

#[CoversClass(EnumTypeDefinition::class)]
#[CoversClass(EnumValueDefinition::class)]
#[CoversClass(EnumValueDefinitions::class)]
final class EnumTypeDefinitionTest extends TestCase
{

    public function test_simple(): void
    {
        $definition = new EnumTypeDefinition(name: 'Episode', valueDefinitions: new EnumValueDefinitions(new EnumValueDefinition('NEWHOPE'), new EnumValueDefinition('EMPIRE'), new EnumValueDefinition('JEDI')));

        $expected = <<<GRAPHQL
            enum Episode {
              NEWHOPE
              EMPIRE
              JEDI
            }

            GRAPHQL;
        self::assertSame($expected, $definition->render());
    }

    public function test_description_and_directives(): void
    {
        $definition = new EnumTypeDefinition(name: 'Episode', valueDefinitions: new EnumValueDefinitions(new EnumValueDefinition('NEWHOPE'), new EnumValueDefinition(name: 'EMPIRE', description: 'Some value description'), new EnumValueDefinition('JEDI')), description: 'Some enum description');

        $expected = <<<GRAPHQL
            """
            Some enum description
            """
            enum Episode {
              NEWHOPE
              """
              Some value description
              """
              EMPIRE
              JEDI
            }

            GRAPHQL;
        self::assertSame($expected, $definition->render());
    }

}
