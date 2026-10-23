<?php

declare(strict_types=1);

namespace Tests\Unit\UseCases;

use App\application\ports\spi\UtilisateurRepositoryInterface;
use App\application\usecases\ObtenirProfil;
use PHPUnit\Framework\TestCase;

final class ObtenirProfilTest extends TestCase
{
    public function testLeMotDePasseNestJamaisRenvoye(): void
    {
        $repository = $this->createStub(UtilisateurRepositoryInterface::class);
        $repository->method('findById')->willReturn([
            'id_utilisateur' => 2,
            'nom' => 'Dupont',
            'password' => 'un-hash',
        ]);

        $profil = (new ObtenirProfil($repository))->execute(2);

        $this->assertSame('Dupont', $profil['nom']);
        $this->assertArrayNotHasKey('password', $profil);
    }

    public function testUtilisateurInexistant(): void
    {
        $repository = $this->createStub(UtilisateurRepositoryInterface::class);
        $repository->method('findById')->willReturn(null);

        $this->assertNull((new ObtenirProfil($repository))->execute(404));
    }
}
