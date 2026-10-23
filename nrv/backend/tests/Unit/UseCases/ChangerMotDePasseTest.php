<?php

declare(strict_types=1);

namespace Tests\Unit\UseCases;

use App\application\ports\spi\UtilisateurRepositoryInterface;
use App\application\usecases\ChangerMotDePasse;
use DomainException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ChangerMotDePasseTest extends TestCase
{
    private UtilisateurRepositoryInterface&MockObject $repository;
    private ChangerMotDePasse $useCase;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(UtilisateurRepositoryInterface::class);
        $this->useCase = new ChangerMotDePasse($this->repository);

        $this->repository->method('findById')->willReturnMap([
            [1, ['id_utilisateur' => 1, 'password' => password_hash('ancienmdp123', PASSWORD_DEFAULT)]],
            [99, null],
        ]);
    }

    public function testChangementReussi(): void
    {
        $this->repository->expects($this->once())
            ->method('updatePassword')
            ->with(1, $this->callback(fn (string $hash) => password_verify('nouveaumdp123', $hash)));

        $this->assertTrue($this->useCase->execute(1, 'ancienmdp123', 'nouveaumdp123'));
    }

    public function testMauvaisMotDePasseActuel(): void
    {
        $this->repository->expects($this->never())->method('updatePassword');

        $this->assertFalse($this->useCase->execute(1, 'pas-le-bon', 'nouveaumdp123'));
    }

    public function testUtilisateurIntrouvable(): void
    {
        $this->repository->expects($this->never())->method('updatePassword');

        $this->assertFalse($this->useCase->execute(99, 'ancienmdp123', 'nouveaumdp123'));
    }

    public function testNouveauMotDePasseTropCourt(): void
    {
        $this->repository->expects($this->never())->method('updatePassword');

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('mot_de_passe_invalide');

        $this->useCase->execute(1, 'ancienmdp123', 'court');
    }

    public function testNouveauMotDePasseTropLong(): void
    {
        $this->expectException(DomainException::class);

        $this->useCase->execute(1, 'ancienmdp123', str_repeat('a', 4097));
    }

    public function testLeMotDePasseActuelEstVerifieAvantLeNouveau(): void
    {
        // mauvais mdp actuel + nouveau trop court : on doit avoir false, pas une exception
        $this->assertFalse($this->useCase->execute(1, 'pas-le-bon', 'court'));
    }
}
