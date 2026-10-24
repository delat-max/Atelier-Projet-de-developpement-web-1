<?php

declare(strict_types=1);

namespace Tests\Unit\UseCases;

use App\application\ports\spi\PanierRepositoryInterface;
use App\application\usecases\GererPanier;
use DomainException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class GererPanierTest extends TestCase
{
    private PanierRepositoryInterface&MockObject $repository;
    private GererPanier $useCase;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(PanierRepositoryInterface::class);
        $this->useCase = new GererPanier($this->repository);
    }

    public function testObtenirRenvoieLePanierDuRepository(): void
    {
        $panier = ['articles' => [], 'total' => 0];
        $this->repository->expects($this->once())
            ->method('obtenirPanierEnCours')
            ->with(8)
            ->willReturn($panier);

        $this->assertSame($panier, $this->useCase->obtenir(8));
    }

    public function testSynchronisationSimple(): void
    {
        $this->repository->expects($this->once())
            ->method('synchroniserPanier')
            ->with(8, [
                ['id_soiree' => 1, 'type_tarif' => 'normal', 'quantite' => 2],
                ['id_soiree' => 2, 'type_tarif' => 'reduit', 'quantite' => 1],
            ])
            ->willReturn(['ok' => true]);

        $resultat = $this->useCase->synchroniser(8, [
            ['id_soiree' => 1, 'quantite' => 2, 'type_tarif' => 'normal'],
            ['id_soiree' => 2, 'quantite' => 1, 'type_tarif' => 'reduit'],
        ]);

        $this->assertSame(['ok' => true], $resultat);
    }

    public function testTarifNormalParDefaut(): void
    {
        $this->repository->expects($this->once())
            ->method('synchroniserPanier')
            ->with(8, [['id_soiree' => 3, 'type_tarif' => 'normal', 'quantite' => 1]])
            ->willReturn([]);

        $this->useCase->synchroniser(8, [['id_soiree' => 3, 'quantite' => 1]]);
    }

    public function testLesDoublonsSontRegroupes(): void
    {
        $this->repository->expects($this->once())
            ->method('synchroniserPanier')
            ->with(8, [
                ['id_soiree' => 1, 'type_tarif' => 'normal', 'quantite' => 5],
                ['id_soiree' => 1, 'type_tarif' => 'reduit', 'quantite' => 1],
            ])
            ->willReturn([]);

        $this->useCase->synchroniser(8, [
            ['id_soiree' => 1, 'quantite' => 2],
            ['id_soiree' => 1, 'quantite' => 1, 'type_tarif' => 'reduit'],
            ['id_soiree' => 1, 'quantite' => 3, 'type_tarif' => 'normal'],
        ]);
    }

    public function testLesNombresEnTexteSontAcceptes(): void
    {
        // le JSON du front peut envoyer "2" au lieu de 2
        $this->repository->expects($this->once())
            ->method('synchroniserPanier')
            ->with(8, [['id_soiree' => 1, 'type_tarif' => 'pass_3_jours', 'quantite' => 2]])
            ->willReturn([]);

        $this->useCase->synchroniser(8, [['id_soiree' => '4', 'quantite' => '2', 'type_tarif' => 'pass_3_jours']]);
    }

    public function testPanierVide(): void
    {
        $this->repository->expects($this->once())
            ->method('synchroniserPanier')
            ->with(8, [])
            ->willReturn([]);

        $this->useCase->synchroniser(8, []);
    }

    #[DataProvider('articlesInvalides')]
    public function testArticleInvalide(mixed $article): void
    {
        $this->repository->expects($this->never())->method('synchroniserPanier');

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('article_invalide');

        $this->useCase->synchroniser(8, [$article]);
    }

    public static function articlesInvalides(): array
    {
        return [
            'pas un tableau' => ['coucou'],
            'id soiree manquant' => [['quantite' => 1]],
            'id soiree a zero' => [['id_soiree' => 0, 'quantite' => 1]],
            'id soiree negatif' => [['id_soiree' => -2, 'quantite' => 1]],
            'id soiree en texte' => [['id_soiree' => 'abc', 'quantite' => 1]],
            'quantite manquante' => [['id_soiree' => 1]],
            'quantite a zero' => [['id_soiree' => 1, 'quantite' => 0]],
            'quantite decimale' => [['id_soiree' => 1, 'quantite' => 1.5]],
            'tarif inconnu' => [['id_soiree' => 1, 'quantite' => 1, 'type_tarif' => 'gratuit']],
        ];
    }

    public function testUnSeulArticleInvalideBloqueToutLePanier(): void
    {
        $this->repository->expects($this->never())->method('synchroniserPanier');
        $this->expectException(DomainException::class);

        $this->useCase->synchroniser(8, [
            ['id_soiree' => 1, 'quantite' => 2],
            ['id_soiree' => 2, 'quantite' => -1],
        ]);
    }
}
