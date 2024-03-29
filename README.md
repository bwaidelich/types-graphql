# types-graphql

Integration for the [wwwision/types](https://github.com/bwaidelich/types) package that allows to generate GraphQL schemas from PHP code

## Usage

This package can be installed via [composer](https://getcomposer.org):

```bash
composer require wwwision/types-graphql
```

To generate a GraphQL schema, create a class with at least one public method with a `Query` attribute:

```php
final class SomeApi {

    #[Query]
    public function ping(string $input): string
    {
        return strtoupper($input);
    }
}
```

Now, this API can be used to create a GraphQL schema:

```php
// ...
$generator = new GraphQLGenerator();
$schema = $generator->generate(SomeApi::class)->render();

assert($schema === 'type Query {
  ping(input: String!): String!
}
');
```

### Advanced types

If you API class refers to more complex types (using attributes from the [wwwision/types](https://github.com/bwaidelich/types) package) in method parameters or return types, they will be added to the schema too.

<details>
<summary><b>Example: Advanced types</b></summary>


1. Given you have the following classes defined:

```php
#[StringBased]
final class GivenName {
    private function __construct(public readonly string $value) {}
}

#[StringBased]
final class FamilyName {
    private function __construct(public readonly string $value) {}
}

final class FullName {
    public function __construct(
        public readonly GivenName $givenName,
        public readonly FamilyName $familyName,
    ) {}
}

#[Description('honorific title of a person')]
enum HonorificTitle
{
    #[Description('for men, regardless of marital status, who do not have another professional or academic title')]
    case MR;
    #[Description('for married women who do not have another professional or academic title')]
    case MRS;
    #[Description('for girls, unmarried women and married women who continue to use their maiden name')]
    case MISS;
    #[Description('for women, regardless of marital status or when marital status is unknown')]
    case MS;
    #[Description('for any other title that does not match the above')]
    case OTHER;
}

#[Description('A contact in the system')]
final class Contact {
    public function __construct(
        public readonly HonorificTitle $title,
        public readonly FullName $name,
        #[Description('Whether the contact is registered or not')]
        public bool $isRegistered = false,
    ) {}
}

#[ListBased(itemClassName: Contact::class)]
final class Contacts {
    private function __construct(private readonly array $contacts) {}
}
```

2. ...and this API class:

```php
// ...
final class SomeApi {

    #[Query]
    public function findContactsByFamilyName(FamilyName $familyName): Contacts
    {
        // ...
    }

    #[Mutation]
    public function addContact(Contact $newContact): bool
    {
        // ...
    }

}
```

3. The GraphQL schema is more verbose now:

```php
// ...
$generator = new GraphQLGenerator();
$schema = $generator->generate(SomeApi::class)->render();

$expectedSchema = <<<GRAPHQL
type Query {
  findContactsByFamilyName(familyName: FamilyName!): [Contact!]!
}

type Mutation {
  addContact(newContact: ContactInput!): Boolean!
}

scalar FamilyName

"""
honorific title of a person
"""
enum HonorificTitle {
  """
  for men, regardless of marital status, who do not have another professional or academic title
  """
  MR
  """
  for married women who do not have another professional or academic title
  """
  MRS
  """
  for girls, unmarried women and married women who continue to use their maiden name
  """
  MISS
  """
  for women, regardless of marital status or when marital status is unknown
  """
  MS
  """
  for any other title that does not match the above
  """
  OTHER
}

scalar GivenName

type FullName {
  givenName: GivenName!
  familyName: FamilyName!
}

type Contact {
  """ honorific title of a person """
  title: HonorificTitle!
  name: FullName!
  """ Whether the contact is registered or not """
  isRegistered: Boolean
}

input FullNameInput {
  givenName: GivenName!
  familyName: FamilyName!
}

input ContactInput {
  """ honorific title of a person """
  title: HonorificTitle!
  name: FullNameInput!
  """ Whether the contact is registered or not """
  isRegistered: Boolean
}

GRAPHQL;

assert($schema === $expectedSchema);
```

</details>

### Type constraints

The GraphQL schema does not have a notion of advanced type constraints.
But the GraphQLGenerator can turn them into [graphql-constraint](https://www.npmjs.com/package/graphql-constraint-directive) directives
that can be interpreted by consumers.
It also adds the constraint rules to descriptions of the corresponding fields.

<details>
<summary><b>Example: Type constraints</b></summary>

```php
#[StringBased(minLength: 1, maxLength: 200)]
final class Name {
    private function __construct(public readonly string $value) {}
}

#[IntegerBased(minimum: 1, maximum: 130)]
final class Age {
    private function __construct(public readonly int $value) {}
}

#[ListBased(itemClassName: Name::class, minCount: 1, maxCount: 5)]
final class Names {
    private function __construct(private readonly array $names) {}
}

final class SomeApi {
    #[Query]
    public function oldestPerson(Names $someNames): ?Age
    {
        // ...
    }
}

$generator = new GraphQLGenerator();
$schema = $generator->generate(SomeApi::class)->render();

$expectedSchema = <<<GRAPHQL
"""
Custom constraint directive (see https://www.npmjs.com/package/graphql-constraint-directive)
"""
directive @constraint(minLength: Int maxLength: Int pattern: String format: String min: Int max: Int minItems: Int maxItems: Int) on FIELD_DEFINITION | SCALAR | ARGUMENT_DEFINITION | INPUT_FIELD_DEFINITION


type Query {
  oldestPerson(someNames: [Name!]! @constraint(minItems: 1 maxItems: 5)): Age
}

"""

*Constraints:*
* Minimum length: `1`
* Maximum length: `200`
"""
scalar Name @constraint(minLength: 1 maxLength: 200)

"""

*Constraints:*
* Minimum value: `1`
* Maximum value: `130`
"""
scalar Age @constraint(min: 1 max: 130)

GRAPHQL;

assert($schema === $expectedSchema);

```

</details>

### Custom resolvers

With version [1.2.0](https://github.com/bwaidelich/types-graphql/releases/tag/1.2.0) custom functions can be registered that extend the behavior of types dynamically.

> **Note**
> The signature of the custom resolver closure has to contain the extended type as first argument and specify the return type, for example: `new CustomResolver('SomeObject', 'someCustomField', fn (SomeObject $thisIsRequired, string $thisIsOptional): bool => true)`

<details>
<summary><b>Example: Custom resolvers</b></summary>

```php
final class User {
    public function __construct(
        public readonly string $givenName,
        public readonly string $familyName,
    ) {}
}

#[ListBased(itemClassName: User::class)]
final class Users {
}

final class SomeApi {
    #[Query]
    public function users(): ?Users
    {
        // ...
    }
}

$generator = new GraphQLGenerator();
$customResolvers = CustomResolvers::create(new CustomResolver('User', 'fullName', fn (User $user): string => $user->givenName . ' ' . $user->familyName));
$schema = $generator->generate(SomeApi::class, $customResolvers)->render();

$expectedSchema = <<<GRAPHQL
type Query {
  users: [User!]
}

type User {
  givenName: String!
  familyName: String!
  fullName: String!
}

GRAPHQL;

assert($schema === $expectedSchema);

```

</details>

## Contribution

Contributions in the form of [issues](https://github.com/bwaidelich/types-graphql/issues) or [pull requests](https://github.com/bwaidelich/types-graphql/pulls) are highly appreciated

## License

See [LICENSE](./LICENSE)
