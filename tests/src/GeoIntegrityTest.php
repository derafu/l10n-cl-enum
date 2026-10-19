<?php

declare(strict_types=1);

/**
 * Derafu: L10n CL Enum - Chilean Enum Repository.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\L10n\Cl\TestsEnum;

use Derafu\L10n\Cl\Enum\Comuna;
use Derafu\L10n\Cl\Enum\Provincia;
use Derafu\L10n\Cl\Enum\Region;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Geographic codes and parent/child collections form a consistent hierarchy.
 */
#[CoversClass(Comuna::class)]
#[CoversClass(Provincia::class)]
#[CoversClass(Region::class)]
final class GeoIntegrityTest extends TestCase
{
    /**
     * @return array<string, array{Region|Provincia|Comuna, string, string}>
     */
    public static function knownTerritories(): array
    {
        return [
            'single-digit region' => [Region::TARAPACA, '01', 'Tarapacá'],
            'two-digit region' => [Region::METROPOLITANA, '13', 'Metropolitana'],
            'region with accented name' => [Region::NUBLE, '16', 'Ñuble'],
            'region with multiple words' => [Region::ARICA_PARINACOTA, '15', 'Arica y Parinacota'],
            'short province code' => [Provincia::IQUIQUE, '011', 'Iquique'],
            'province with accented name' => [Provincia::CHANARAL, '032', 'Chañaral'],
            'three-digit province code' => [Provincia::DIGUILLIN, '161', 'Diguillín'],
            'short commune code' => [Comuna::IQUIQUE, '01101', 'Iquique'],
            'commune with accented enum name' => [Comuna::CAMIÑA, '01402', 'Camiña'],
            'five-digit commune code' => [Comuna::SANTIAGO, '13101', 'Santiago'],
            'commune with accented name' => [Comuna::CHILLAN, '16101', 'Chillán'],
        ];
    }

    #[DataProvider('knownTerritories')]
    public function testKnownTerritoriesHaveStableCodesAndAccentedNames(
        Region|Provincia|Comuna $territory,
        string $code,
        string $name
    ): void {
        $this->assertSame($code, $territory->getCodigo());
        $this->assertSame($name, $territory->getGlosa());
        $this->assertSame($name, $territory->getNombre());
    }

    public function testAllCodesHaveTheCorrectWidthAndPreserveTheirNumericIdentity(): void
    {
        foreach ([
            [Region::cases(), 2],
            [Provincia::cases(), 3],
            [Comuna::cases(), 5],
        ] as [$territories, $width]) {
            foreach ($territories as $territory) {
                $code = $territory->getCodigo();
                $this->assertMatchesRegularExpression('/^[0-9]{' . $width . '}$/D', $code, $territory->name);
                $this->assertSame($territory->value, (int) $code, $territory->name);
                $this->assertSame($territory, $territory::from((int) $code));
            }
        }
    }

    public function testAllTerritoriesHaveANonemptyGlosaAndAnEquivalentName(): void
    {
        foreach ([...Region::cases(), ...Provincia::cases(), ...Comuna::cases()] as $territory) {
            $this->assertNotSame('', trim($territory->getGlosa()), $territory->name);
            $this->assertSame($territory->getGlosa(), $territory->getNombre(), $territory->name);
        }
    }

    public function testEveryCommuneAgreesWithItsProvinceAboutTheRegion(): void
    {
        foreach (Comuna::cases() as $commune) {
            $province = $commune->getProvincia();
            $region = $commune->getRegion();

            $this->assertSame($province->getRegion(), $region, $commune->name);
            $this->assertContains($commune, $province->getComunas(), $commune->name);
            $this->assertContains($commune, $region->getComunas(), $commune->name);
        }
    }

    public function testRegionsPartitionAllProvincesWithoutMissingOrRepeatedMembers(): void
    {
        $actual = [];
        foreach (Region::cases() as $region) {
            $provinces = $region->getProvincias();
            $this->assertNotEmpty($provinces, $region->name);
            foreach ($provinces as $province) {
                $this->assertSame($region, $province->getRegion(), $province->name);
                $actual[] = $province->value;
            }
        }
        $expected = array_map(fn (Provincia $province) => $province->value, Provincia::cases());
        sort($actual);
        sort($expected);
        $this->assertSame($expected, $actual);
    }

    public function testProvincesPartitionAllCommunesWithoutMissingOrRepeatedMembers(): void
    {
        $actual = [];
        foreach (Provincia::cases() as $province) {
            $communes = $province->getComunas();
            $this->assertNotEmpty($communes, $province->name);
            foreach ($communes as $commune) {
                $this->assertSame($province, $commune->getProvincia(), $commune->name);
                $actual[] = $commune->value;
            }
        }
        $expected = array_map(fn (Comuna $commune) => $commune->value, Comuna::cases());
        sort($actual);
        sort($expected);
        $this->assertSame($expected, $actual);
    }

    public function testRegionsPartitionAllCommunesWithoutMissingOrRepeatedMembers(): void
    {
        $actual = [];
        foreach (Region::cases() as $region) {
            $communes = $region->getComunas();
            $this->assertNotEmpty($communes, $region->name);
            foreach ($communes as $commune) {
                $this->assertSame($region, $commune->getRegion(), $commune->name);
                $actual[] = $commune->value;
            }
        }
        $expected = array_map(fn (Comuna $commune) => $commune->value, Comuna::cases());
        sort($actual);
        sort($expected);
        $this->assertSame($expected, $actual);
    }

    public function testKnownMembershipsAnchorTheHierarchyToConcreteTerritories(): void
    {
        $this->assertEqualsCanonicalizing(
            [Provincia::IQUIQUE, Provincia::TAMARUGAL],
            array_values(Region::TARAPACA->getProvincias())
        );
        $this->assertEqualsCanonicalizing(
            [Provincia::DIGUILLIN, Provincia::ITATA, Provincia::PUNILLA],
            array_values(Region::NUBLE->getProvincias())
        );
        $this->assertEqualsCanonicalizing(
            [Comuna::IQUIQUE, Comuna::ALTO_HOSPICIO],
            array_values(Provincia::IQUIQUE->getComunas())
        );
        $this->assertSame(Provincia::DIGUILLIN, Comuna::CHILLAN->getProvincia());
        $this->assertSame(Region::NUBLE, Comuna::CHILLAN->getRegion());
    }
}
