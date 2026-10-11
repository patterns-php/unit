<?php

declare(strict_types=1);

namespace Patterns\Tests;

use PHPUnit\Framework\TestCase;
use Patterns\IOperator;
use Patterns\IUnit;
use Patterns\Tests\Fakes\TestOperator;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;

/**
 * IOperator is the third interface in this package and the odd one out: it has
 * no identity, no version and no DNA, because it is not a thing - it is a way of
 * reaching things.
 *
 * Which is exactly why it needs pinning. IUnit's argument is provenance; this
 * one's is restraint. The temptation is to give it a `dna()` for consistency, or
 * a `create(array $props)` to match its neighbours in this package, or a
 * `schema()` so a unit can describe itself as a tool. Each would look tidy and
 * each would make it unusable for the case it exists for: a catalogue of an
 * application's own service methods, most of which are not units and none of
 * which can be built from an array of props.
 */
final class OperatorContractTest extends TestCase
{
    // -----------------------------------------------------------------------
    // The contract must not grow
    // -----------------------------------------------------------------------

    public function testIOperatorDeclaresExactlyThreeMethods(): void
    {
        $this->assertSame(['execute', 'help', 'tools'], self::declaredMethods(IOperator::class));
    }

    public function testItOwnsNoIdentity(): void
    {
        $class = new ReflectionClass(IOperator::class);

        $this->assertFalse($class->isSubclassOf(IUnit::class), 'an operator is not a unit');
        $this->assertFalse($class->hasMethod('dna'), 'and has no DNA to declare');
        $this->assertFalse($class->hasMethod('whoami'), 'and nothing to say about itself');
        $this->assertFalse($class->hasMethod('schema'), 'and does not describe itself as anything, tools included');
    }

    public function testItIsNotBuiltFromAnArrayOfProps(): void
    {
        // IUnit and IUnitSchema both declare a static create(array): their DNA is
        // data, so it can be handed over as data. An operator is the opposite -
        // it is wired to live services and callables - so a props factory here
        // would be a signature nobody could honour.
        $this->assertFalse(
            (new ReflectionClass(IOperator::class))->hasMethod('create'),
            'an operator takes live collaborators, not props'
        );
    }

    public function testItCarriesNoAiVocabulary(): void
    {
        $ai = ['ask', 'chat', 'prompt', 'prompt', 'model', 'provider', 'token', 'tool_call', 'message'];

        foreach (self::declaredMethods(IOperator::class) as $method) {
            $this->assertNotContains(
                strtolower($method),
                $ai,
                'the pattern must not imply a model: ' . $method . '() belongs to a consumer, not here'
            );
        }
    }

    // -----------------------------------------------------------------------
    // The PHP-specific part
    // -----------------------------------------------------------------------

    public function testToolsReturnsAnArray(): void
    {
        $this->assertSame('array', self::returnTypeName(new ReflectionMethod(IOperator::class, 'tools')));
    }

    public function testExecuteReturnsMixed(): void
    {
        // Deliberately mixed: a tool may return a list of products, a string, or
        // a value object. Constraining it here would push every tool into a
        // wrapper nobody asked for.
        $this->assertSame('mixed', self::returnTypeName(new ReflectionMethod(IOperator::class, 'execute')));
    }

    public function testHelpReturnsAString(): void
    {
        $this->assertSame('string', self::returnTypeName(new ReflectionMethod(IOperator::class, 'help')));
    }

    public function testExecuteTakesANameAndAnOptionalArgumentsArray(): void
    {
        $execute = new ReflectionMethod(IOperator::class, 'execute');
        $parameters = $execute->getParameters();

        $this->assertCount(2, $parameters);
        $this->assertSame('string', self::typeName($parameters[0]->getType()));
        $this->assertSame('array', self::typeName($parameters[1]->getType()));
        $this->assertTrue($parameters[1]->isOptional(), 'a tool with no arguments must be callable without saying so');
    }

    // -----------------------------------------------------------------------
    // Satisfiable with nothing else
    // -----------------------------------------------------------------------

    public function testTheContractNeedsNoBaseClass(): void
    {
        $this->assertFalse(
            (new ReflectionClass(TestOperator::class))->getParentClass(),
            'TestOperator must not need to extend anything'
        );
    }

    public function testAConsumerCanRelyOnTheInterfaceAlone(): void
    {
        // What a model-facing consumer does: read the catalogue, dispatch by
        // name. It knows nothing about what is behind the operator.
        $describe = static function (IOperator $operator, string $tool, array $arguments = []): string {
            $catalogue = $operator->tools();

            if (!isset($catalogue[$tool])) {
                return $operator->help();
            }

            return sprintf('%s: %s', $catalogue[$tool]['name'], json_encode($operator->execute($tool, $arguments)));
        };

        $operator = new TestOperator([
            'products.top' => static fn (array $a): array => ['limit' => $a['limit'] ?? 10],
        ]);

        $this->assertSame('products.top: {"limit":5}', $describe($operator, 'products.top', ['limit' => 5]));
        $this->assertSame('I can do 1 things.', $describe($operator, 'nope'));
    }

    public function testTheCatalogueCarriesWhatAProviderNeeds(): void
    {
        $catalogue = (new TestOperator(['products.top' => static fn (): array => []]))->tools();
        $entry = $catalogue['products.top'];

        $this->assertSame(['name', 'description', 'parameters'], array_keys($entry));
        $this->assertSame('products.top', $entry['name']);
        $this->assertIsArray($entry['parameters'], 'the schema travels with the tool, as data');
        $this->assertSame('object', $entry['parameters']['type']);
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    /**
     * @return list<string>
     */
    private static function declaredMethods(string $interface): array
    {
        $own = [];

        foreach ((new ReflectionClass($interface))->getMethods() as $method) {
            if ($method->getDeclaringClass()->getName() === $interface) {
                $own[] = $method->getName();
            }
        }

        sort($own);

        return $own;
    }

    private static function returnTypeName(ReflectionMethod $method): string
    {
        return self::typeName($method->getReturnType());
    }

    private static function typeName(mixed $type): string
    {
        return $type instanceof ReflectionNamedType ? $type->getName() : (string) $type;
    }
}
