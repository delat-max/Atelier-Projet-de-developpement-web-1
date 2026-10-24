<?php

declare(strict_types=1);

namespace Tests\Unit\UseCases;

use App\application\ports\spi\UtilisateurRepositoryInterface;
use App\application\usecases\InscrireUtilisateur;
use DomainException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class InscrireUtilisateurTest extends TestCase
{
    private UtilisateurRepositoryInterface&MockObject $repository;
    private InscrireUtilisateur $useCase;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(UtilisateurRepositoryInterface::class);
        $this->useCase = new InscrireUtilisateur($this->repository);
    }

    public function testInscriptionValideRetourneLId(): void
    {
        $this->repository->method('findByEmail')->willReturn(null);
        $this->repository->expects($this->once())
            ->method('create')
            ->with(
                'Dupont',
                'Marie',
                'marie@exemple.fr',
                $this->callback(fn (string $hash) => password_verify('motdepasse123', $hash))
            )
            ->willReturn(42);

        $id = $this->useCase->execute('Dupont', 'Marie', 'marie@exemple.fr', 'motdepasse123');

        $this->assertSame(42, $id);
    }

    public function testLesChampsSontNettoyes(): void
    {
        $this->repository->method('findByEmail')->willReturn(null);
        $this->repository->expects($this->once())
            ->method('create')
            ->with('Dupont', 'Marie', 'marie@exemple.fr', $this->anything())
            ->willReturn(1);

        $this->useCase->execute('  Dupont ', ' Marie', '  Marie@Exemple.FR ', 'motdepasse123');
    }

    public function testLeMotDePasseNestPasStockeEnClair(): void
    {
        $this->repository->method('findByEmail')->willReturn(null);
        $this->repository->expects($this->once())
            ->method('create')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->logicalNot($this->equalTo('motdepasse123'))
            )
            ->willReturn(1);

        $this->useCase->execute('Dupont', 'Marie', 'marie@exemple.fr', 'motdepasse123');
    }

    #[DataProvider('donneesInvalides')]
    public function testDonneesInvalidesRefusees(
        string $nom,
        string $prenom,
        string $email,
        string $password,
        string $erreur
    ): void {
        $this->repository->method('findByEmail')->willReturn(null);
        $this->repository->expects($this->never())->method('create');

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage($erreur);

        $this->useCase->execute($nom, $prenom, $email, $password);
    }

    public static function donneesInvalides(): array
    {
        return [
            'nom vide' => ['', 'Marie', 'marie@exemple.fr', 'motdepasse123', 'nom_prenom_invalides'],
            'nom avec que des espaces' => ['   ', 'Marie', 'marie@exemple.fr', 'motdepasse123', 'nom_prenom_invalides'],
            'prenom vide' => ['Dupont', '', 'marie@exemple.fr', 'motdepasse123', 'nom_prenom_invalides'],
            'nom trop long' => [str_repeat('a', 51), 'Marie', 'marie@exemple.fr', 'motdepasse123', 'nom_prenom_invalides'],
            'prenom trop long' => ['Dupont', str_repeat('a', 51), 'marie@exemple.fr', 'motdepasse123', 'nom_prenom_invalides'],
            'email sans arobase' => ['Dupont', 'Marie', 'marie.exemple.fr', 'motdepasse123', 'email_invalide'],
            'email trop long' => ['Dupont', 'Marie', str_repeat('a', 95) . '@ex.fr', 'motdepasse123', 'email_invalide'],
            'mot de passe trop court' => ['Dupont', 'Marie', 'marie@exemple.fr', '123456789', 'mot_de_passe_invalide'],
            'mot de passe trop long' => ['Dupont', 'Marie', 'marie@exemple.fr', str_repeat('a', 4097), 'mot_de_passe_invalide'],
        ];
    }

    public function testLimitesAccepteesPourNomEtMotDePasse(): void
    {
        $this->repository->method('findByEmail')->willReturn(null);
        $this->repository->method('create')->willReturn(7);

        $id = $this->useCase->execute(str_repeat('a', 50), str_repeat('b', 50), 'marie@exemple.fr', '1234567890');

        $this->assertSame(7, $id);
    }

    public function testEmailDejaUtilise(): void
    {
        $this->repository->method('findByEmail')
            ->with('marie@exemple.fr')
            ->willReturn(['id_utilisateur' => 3]);
        $this->repository->expects($this->never())->method('create');

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('email_deja_utilise');

        $this->useCase->execute('Dupont', 'Marie', 'marie@exemple.fr', 'motdepasse123');
    }
}
