<?php

declare(strict_types=1);

namespace Tests\Unit\UseCases;

use App\application\ports\spi\UtilisateurRepositoryInterface;
use App\application\usecases\ModifierProfil;
use DomainException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ModifierProfilTest extends TestCase
{
    private UtilisateurRepositoryInterface&MockObject $repository;
    private ModifierProfil $useCase;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(UtilisateurRepositoryInterface::class);
        $this->useCase = new ModifierProfil($this->repository);
    }

    public function testModificationValideAvecNettoyage(): void
    {
        $this->repository->expects($this->once())
            ->method('updateProfile')
            ->with(3, 'Martin', 'Paul', 'paul@exemple.fr');

        $this->useCase->execute(3, ' Martin ', 'Paul  ', ' Paul@EXEMPLE.fr');
    }

    #[DataProvider('donneesInvalides')]
    public function testDonneesInvalidesRefusees(string $nom, string $prenom, string $email, string $erreur): void
    {
        $this->repository->expects($this->never())->method('updateProfile');

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage($erreur);

        $this->useCase->execute(3, $nom, $prenom, $email);
    }

    public static function donneesInvalides(): array
    {
        return [
            'nom vide' => ['', 'Paul', 'paul@exemple.fr', 'nom_prenom_invalides'],
            'prenom vide' => ['Martin', '  ', 'paul@exemple.fr', 'nom_prenom_invalides'],
            'nom trop long' => [str_repeat('a', 51), 'Paul', 'paul@exemple.fr', 'nom_prenom_invalides'],
            'email invalide' => ['Martin', 'Paul', 'paul@', 'email_invalide'],
            'email vide' => ['Martin', 'Paul', '', 'email_invalide'],
        ];
    }
}
