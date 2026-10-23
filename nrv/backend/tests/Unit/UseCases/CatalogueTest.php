<?php

declare(strict_types=1);

namespace Tests\Unit\UseCases;

use App\application\ports\spi\CatalogRepositoryInterface;
use App\application\usecases\ListerSpectacles;
use App\application\usecases\ObtenirDetailsSoiree;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class CatalogueTest extends TestCase
{
    private CatalogRepositoryInterface&MockObject $catalog;

    protected function setUp(): void
    {
        $this->catalog = $this->createMock(CatalogRepositoryInterface::class);
    }

    public function testListerTransmetLesFiltres(): void
    {
        $this->catalog->expects($this->once())
            ->method('findAllSpectacles')
            ->with('2026-06-12', 'rock', 'titre', 9, 18)
            ->willReturn([['titre' => 'Concert']]);

        $resultat = (new ListerSpectacles($this->catalog))->execute('2026-06-12', 'rock', 'titre', 9, 18);

        $this->assertCount(1, $resultat);
    }

    public function testListerSansFiltre(): void
    {
        $this->catalog->expects($this->once())
            ->method('findAllSpectacles')
            ->with(null, null, null, null, null)
            ->willReturn([]);

        $this->assertSame([], (new ListerSpectacles($this->catalog))->execute());
    }

    public function testCountDatesEtGenres(): void
    {
        $this->catalog->method('countAllSpectacles')->with('2026-06-12', null)->willReturn(4);
        $this->catalog->method('findAvailableDates')->willReturn(['2026-06-12', '2026-06-13']);
        $this->catalog->method('findAvailableGenres')->willReturn(['rock', 'metal']);

        $useCase = new ListerSpectacles($this->catalog);

        $this->assertSame(4, $useCase->count('2026-06-12'));
        $this->assertSame(['2026-06-12', '2026-06-13'], $useCase->dates());
        $this->assertSame(['rock', 'metal'], $useCase->genres());
    }

    public function testDetailsSoireeAvecSesSpectacles(): void
    {
        $this->catalog->method('findSoireeById')->with(2)->willReturn(['id_soiree' => 2, 'nom' => 'Soirée metal']);
        $this->catalog->method('findSpectaclesBySoireeId')->with(2)->willReturn([['titre' => 'A'], ['titre' => 'B']]);

        $soiree = (new ObtenirDetailsSoiree($this->catalog))->execute(2);

        $this->assertSame('Soirée metal', $soiree['nom']);
        $this->assertCount(2, $soiree['spectacles']);
    }

    public function testSoireeIntrouvable(): void
    {
        $this->catalog->method('findSoireeById')->willReturn(null);
        $this->catalog->expects($this->never())->method('findSpectaclesBySoireeId');

        $this->assertNull((new ObtenirDetailsSoiree($this->catalog))->execute(999));
    }
}
