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
use PHPUnit\Framework\TestCase;

#[CoversClass(Comuna::class)]
#[CoversClass(Provincia::class)]
#[CoversClass(Region::class)]
final class GeoTest extends TestCase
{
    public function testAllComunasHaveProvincias(): void
    {
        foreach (Comuna::cases() as $comuna) {
            $this->assertInstanceOf(
                Provincia::class,
                $comuna->getProvincia(),
                sprintf('La comuna %s (%d) no tiene provincia.', $comuna->name, $comuna->value)
            );
        }
    }

    public function testAllProvinciasHaveRegiones(): void
    {
        foreach (Provincia::cases() as $provincia) {
            $this->assertInstanceOf(
                Region::class,
                $provincia->getRegion(),
                sprintf('La provincia %s (%d) no tiene región.', $provincia->name, $provincia->value)
            );
        }
    }

    public function testAllRegionesHaveProvincias(): void
    {
        foreach (Region::cases() as $region) {
            $this->assertNotEmpty(
                $region->getProvincias(),
                sprintf('La región %s (%d) no tiene provincias.', $region->name, $region->value)
            );
        }
    }
}
