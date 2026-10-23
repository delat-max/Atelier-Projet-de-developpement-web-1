<?php

declare(strict_types=1);

namespace Tests\Unit\UseCases;

use App\application\ports\spi\UtilisateurRepositoryInterface;
use App\application\usecases\AuthentifierUtilisateur;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class AuthentifierUtilisateurTest extends TestCase
{
    private UtilisateurRepositoryInterface&MockObject $repository;
    private AuthentifierUtilisateur $useCase;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(UtilisateurRepositoryInterface::class);
        $this->useCase = new AuthentifierUtilisateur($this->repository);
    }

    private function utilisateur(string $hash): array
    {
        return [
            'id_utilisateur' => 5,
            'nom' => 'Dupont',
            'prenom' => 'Marie',
            'email' => 'marie@exemple.fr',
            'password' => $hash,
        ];
    }

    public function testConnexionReussieSansLeMotDePasseDansLeResultat(): void
    {
        $this->repository->method('findByEmail')
            ->willReturn($this->utilisateur(password_hash('motdepasse123', PASSWORD_DEFAULT)));

        $resultat = $this->useCase->execute('marie@exemple.fr', 'motdepasse123');

        $this->assertNotNull($resultat);
        $this->assertSame(5, $resultat['id_utilisateur']);
        $this->assertArrayNotHasKey('password', $resultat);
    }

    public function testEmailNormaliseAvantLaRecherche(): void
    {
        $this->repository->expects($this->once())
            ->method('findByEmail')
            ->with('marie@exemple.fr')
            ->willReturn(null);

        $this->useCase->execute('  MARIE@exemple.fr ', 'motdepasse123');
    }

    public function testUtilisateurInconnu(): void
    {
        $this->repository->method('findByEmail')->willReturn(null);

        $this->assertNull($this->useCase->execute('inconnu@exemple.fr', 'motdepasse123'));
    }

    public function testMauvaisMotDePasse(): void
    {
        $this->repository->method('findByEmail')
            ->willReturn($this->utilisateur(password_hash('motdepasse123', PASSWORD_DEFAULT)));

        $this->assertNull($this->useCase->execute('marie@exemple.fr', 'pas-le-bon-mdp'));
    }

    public function testPasDeRehashSiLeHashEstAJour(): void
    {
        $this->repository->method('findByEmail')
            ->willReturn($this->utilisateur(password_hash('motdepasse123', PASSWORD_DEFAULT)));
        $this->repository->expects($this->never())->method('updatePassword');

        $this->useCase->execute('marie@exemple.fr', 'motdepasse123');
    }

    public function testRehashSiLeHashEstObsolete(): void
    {
        // un hash bcrypt avec un cout faible, comme une vieille base
        $vieuxHash = password_hash('motdepasse123', PASSWORD_BCRYPT, ['cost' => 4]);

        $this->repository->method('findByEmail')->willReturn($this->utilisateur($vieuxHash));
        $this->repository->expects($this->once())
            ->method('updatePassword')
            ->with(5, $this->callback(fn (string $hash) => password_verify('motdepasse123', $hash)));

        $this->assertNotNull($this->useCase->execute('marie@exemple.fr', 'motdepasse123'));
    }
}
