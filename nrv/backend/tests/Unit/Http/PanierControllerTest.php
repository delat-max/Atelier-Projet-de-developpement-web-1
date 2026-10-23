<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use App\adapters\http\PanierController;
use App\application\ports\api\GererPanierInterface;
use DomainException;
use PHPUnit\Framework\MockObject\MockObject;

final class PanierControllerTest extends HttpTestCase
{
    private GererPanierInterface&MockObject $gererPanier;
    private PanierController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gererPanier = $this->createMock(GererPanierInterface::class);
        $this->controller = new PanierController($this->gererPanier);
    }

    public function testObtenirSansEtreConnecte(): void
    {
        $this->gererPanier->expects($this->never())->method('obtenir');

        $reponse = $this->controller->obtenir($this->requete(), $this->reponse());

        $this->assertSame(401, $reponse->getStatusCode());
    }

    public function testObtenirConnecte(): void
    {
        $this->connecter(4);
        $this->gererPanier->method('obtenir')->with(4)->willReturn(['articles' => []]);

        $reponse = $this->controller->obtenir($this->requete(), $this->reponse());

        $this->assertSame(200, $reponse->getStatusCode());
        $this->assertSame(['data' => ['articles' => []]], $this->json($reponse));
    }

    public function testSynchroniserSansEtreConnecte(): void
    {
        $reponse = $this->controller->synchroniser(
            $this->requete('PUT', [], ['articles' => []]),
            $this->reponse()
        );

        $this->assertSame(401, $reponse->getStatusCode());
    }

    public function testSynchroniserAvecUnBodyInvalide(): void
    {
        $this->connecter(4);
        $this->gererPanier->expects($this->never())->method('synchroniser');

        foreach ([[], ['articles' => 'pas une liste'], ['autre' => []]] as $body) {
            $reponse = $this->controller->synchroniser($this->requete('PUT', [], $body), $this->reponse());
            $this->assertSame(400, $reponse->getStatusCode());
        }
    }

    public function testSynchroniserOk(): void
    {
        $this->connecter(4);
        $articles = [['id_soiree' => 1, 'quantite' => 2]];
        $this->gererPanier->expects($this->once())
            ->method('synchroniser')
            ->with(4, $articles)
            ->willReturn(['total' => 30]);

        $reponse = $this->controller->synchroniser(
            $this->requete('PUT', [], ['articles' => $articles]),
            $this->reponse()
        );

        $this->assertSame(200, $reponse->getStatusCode());
        $this->assertSame(['data' => ['total' => 30]], $this->json($reponse));
    }

    public function testMessagesDErreurDuPanier(): void
    {
        $this->connecter(4);
        $cas = [
            'soiree_introuvable' => 'Soirée introuvable',
            'places_insuffisantes' => 'Nombre de places disponibles insuffisant',
            'article_invalide' => 'Données du panier invalides',
        ];

        foreach ($cas as $codeErreur => $message) {
            $gererPanier = $this->createStub(GererPanierInterface::class);
            $gererPanier->method('synchroniser')->willThrowException(new DomainException($codeErreur));
            $controller = new PanierController($gererPanier);

            $reponse = $controller->synchroniser(
                $this->requete('PUT', [], ['articles' => [['id_soiree' => 1, 'quantite' => 1]]]),
                $this->reponse()
            );

            $this->assertSame(400, $reponse->getStatusCode());
            $this->assertSame($message, $this->json($reponse)['error']);
        }
    }

    public function testUnIdDeSessionQuiNestPasUnEntierEstRefuse(): void
    {
        $_SESSION['user_id'] = '4';

        $reponse = $this->controller->obtenir($this->requete(), $this->reponse());

        $this->assertSame(401, $reponse->getStatusCode());
    }
}
