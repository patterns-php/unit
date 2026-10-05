<?php

declare(strict_types=1);

namespace Patterns\Tests;

use JsonSerializable;
use PHPUnit\Framework\TestCase;
use Patterns\IUnit;
use Patterns\IUnitSchema;
use Patterns\Tests\Fakes\TestUnit;
use Patterns\Tests\Fakes\TestUnitSchema;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;

final class UnitContractTest extends TestCase
{
    // -----------------------------------------------------------------------
    // The contract must not grow
    // -----------------------------------------------------------------------

    public function testIUnitDeclaresExactlyThreeMethods(): void
    {
        $this->assertSame(['create', 'dna', 'whoami'], self::declaredMethods(IUnit::class));
    }

    public function testIUnitSchemaDeclaresExactlySevenMethods(): void
    {
        $this->assertSame(
            ['create', 'description', 'id', 'label', 'toArray', 'version', 'versions'],
            self::declaredMethods(IUnitSchema::class)
        );
    }

    // -----------------------------------------------------------------------
    // The PHP-specific part: a typed static factory
    // -----------------------------------------------------------------------

    public function testCreateIsAStaticFactoryReturningSelf(): void
    {
        foreach ([IUnit::class, IUnitSchema::class] as $interface) {
            $create = new ReflectionMethod($interface, 'create');

            $this->assertTrue($create->isStatic(), $interface . '::create() must be static');
            $this->assertSame('self', self::returnTypeName($create), $interface . '::create() must return self');
            $this->assertSame(1, $create->getNumberOfParameters(), $interface . '::create() takes one array');
        }
    }

    public function testDnaReturnsAnIUnitSchema(): void
    {
        $this->assertSame(
            'Patterns\\IUnitSchema',
            self::returnTypeName(new ReflectionMethod(IUnit::class, 'dna'))
        );
    }

    public function testWhoamiReturnsAString(): void
    {
        $this->assertSame('string', self::returnTypeName(new ReflectionMethod(IUnit::class, 'whoami')));
    }

    public function testSchemaIsJsonSerializable(): void
    {
        $this->assertTrue(
            (new ReflectionClass(IUnitSchema::class))->implementsInterface(JsonSerializable::class),
            'DNA must be persistable as JSON'
        );
    }

    // -----------------------------------------------------------------------
    // Satisfiable with nothing else
    // -----------------------------------------------------------------------

    public function testTheContractsNeedNoBaseClass(): void
    {
        foreach ([TestUnit::class, TestUnitSchema::class] as $class) {
            $this->assertFalse(
                (new ReflectionClass($class))->getParentClass(),
                $class . ' must not need to extend anything'
            );
        }
    }

    // -----------------------------------------------------------------------
    // Round-trip
    // -----------------------------------------------------------------------

    public function testSchemaRoundTripsThroughToArrayAndCreate(): void
    {
        $dna = TestUnitSchema::create([
            'id'          => 'supplier-ranking',
            'version'     => '1.1.0',
            'label'       => 'SupplierRanking v1.1.0',
            'description' => 'Ranks suppliers by weighted velocity.',
            'versions'    => [
                ['version' => '1.0.0', 'date' => '2026-01-15', 'notes' => 'Initial release'],
                ['version' => '1.1.0', 'date' => '2026-10-05', 'notes' => 'Weight cadence per supplier'],
            ],
        ]);

        $restored = TestUnitSchema::create($dna->toArray());

        $this->assertSame($dna->toArray(), $restored->toArray());
        $this->assertSame($dna->toArray(), $restored->jsonSerialize());
        $this->assertSame(json_encode($dna), json_encode($restored));
        $this->assertCount(2, $restored->versions());
    }

    // -----------------------------------------------------------------------
    // Provenance - the reason the pattern exists
    // -----------------------------------------------------------------------

    public function testAStoredRecordCanReportWhichUnitVersionProducedIt(): void
    {
        $unit = TestUnit::create(['dna' => [
            'id'      => 'supplier-ranking',
            'version' => '1.1.0',
            'label'   => 'SupplierRanking v1.1.0',
        ]]);

        // what a persistence layer stores next to the data
        $row = ['ean' => '5901234123457', 'value' => 42.5, 'unit' => $unit->dna()->toArray()];

        // what an audit asks later
        $dna = TestUnitSchema::create($row['unit']);

        $this->assertSame('supplier-ranking', $dna->id());
        $this->assertSame('1.1.0', $dna->version());
        $this->assertSame('SupplierRanking v1.1.0', $dna->label());
    }

    // -----------------------------------------------------------------------
    // Reverse dependency
    // -----------------------------------------------------------------------

    public function testAConsumerCanRelyOnTheInterfaceAlone(): void
    {
        $describe = static fn (IUnit $unit): string => sprintf(
            '%s@%s -> %s',
            $unit->dna()->id(),
            $unit->dna()->version(),
            $unit->whoami()
        );

        $unit = TestUnit::create(['dna' => ['id' => 'log', 'version' => '1.0.0', 'label' => 'Logger v1.0.0']]);

        $this->assertSame('log@1.0.0 -> Logger v1.0.0', $describe($unit));
    }

    public function testWhoamiFallsBackToTheLabel(): void
    {
        $unit = TestUnit::create(['dna' => ['id' => 'x', 'version' => '2.0.0', 'label' => 'X v2.0.0']]);

        $this->assertSame('X v2.0.0', $unit->whoami());
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
        $type = $method->getReturnType();

        return $type instanceof ReflectionNamedType ? $type->getName() : (string) $type;
    }
}
