<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use App\adapters\http\CatalogController;
use App\application\ports\api\ListerSpectaclesInterface;
use App\application\ports\api\ObtenirDetailsSoireeInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;

final class CatalogControllerTest extends HttpTestCase
{
    private ListerSpectaclesInterface&MockObject $lister;
    private ObtenirDetailsSoireeInterface&MockObject $details;
    private CatalogController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->lister = $this->createMock(ListerSpectaclesInterface::class);
        $this->details = $this->createMock(ObtenirDetailsSoireeInterface::class);
        $this->controller = new CatalogController($this->lister, $this->details);
    }

    public function testDatesEtGenres(): void
    {
        $this->lister->method('dates')->willReturn(['2026-06-12']);
        $this->lister->method('genres')->willReturn(['rock']);

        $dates = $this->controller->dates($this->requete(), $this->reponse());
        $genres = $this->controller->genres($this->requete(), $this->reponse());

        $this->assertSame(['data' => ['2026-06-12']], $this->json($dates));
        $this->assertSame(['data' => ['rock']], $this->json($genres));
        $this->assertStringContainsString('application/json', $dates->getHeaderLine('Content-Type'));
    }

    public function testListeParDefautPremierePage(): void
    {
        $this->lister->method('count')->with(null, null)->willReturn(20);
        $this->lister->expects($this->once())
            ->method('execute')
            ->with(null, null, null, 9, 0)
            ->willReturn([['titre' => 'A']]);

        $reponse = $this->controller->listerSpectacles($this->requete(), $this->reponse());
        $data = $this->json($reponse);

        $this->assertSame(200, $reponse->getStatusCode());
        $this->assertSame(1, $data['meta']['current_page']);
        $this->assertSame(3, $data['meta']['total_pages']);
        $this->assertSame(20, $data['meta']['total_items']);
    }

    public function testFiltresEtPagination(): void
    {
        $this->lister->method('count')->with('2026-06-12', 'rock')->willReturn(20);
        $this->lister->expects($this->once())
            ->method('execute')
            ->with('2026-06-12', 'rock', 'titre', 9, 9)
            ->willReturn([]);

        $reponse = $this->controller->listerSpectacles(
            $this->requete('GET', ['date' => '2026-06-12', 'genre' => ' rock ', 'tri' => 'titre', 'page' => '2']),
            $this->reponse()
        );

        $this->assertSame(2, $this->json($reponse)['meta']['current_page']);
    }

    public function testPageTropGrandeRameneeALaDerniere(): void
    {
        $this->lister->method('count')->willReturn(10);
        $this->lister->expects($this->once())
            ->method('execute')
            ->with(null, null, null, 9, 9)
            ->willReturn([]);

        $reponse = $this->controller->listerSpectacles($this->requete('GET', ['page' => '50']), $this->reponse());

        $this->assertSame(2, $this->json($reponse)['meta']['current_page']);
    }

    public function testAucunResultat(): void
    {
        $this->lister->method('count')->willReturn(0);
        $this->lister->method('execute')->willReturn([]);

        $data = $this->json($this->controller->listerSpectacles($this->requete(), $this->reponse()));

        $this->assertSame([], $data['data']);
        $this->assertSame(0, $data['meta']['total_pages']);
    }

    #[DataProvider('parametresInvalides')]
    public function testParametresInvalides(array $query, string $erreur): void
    {
        $this->lister->expects($this->never())->method('execute');

        $reponse = $this->controller->listerSpectacles($this->requete('GET', $query), $this->reponse());

        $this->assertSame(400, $reponse->getStatusCode());
        $this->assertSame($erreur, $this->json($reponse)['error']);
    }

    public static function parametresInvalides(): array
    {
        return [
            'date mal formee' => [['date' => '12/06/2026'], 'Date invalide'],
            'date qui existe pas' => [['date' => '2026-02-30'], 'Date invalide'],
            'genre trop long' => [['genre' => str_repeat('a', 241)], 'Style de musique invalide'],
            'tri inconnu' => [['tri' => 'prix'], 'Tri invalide'],
            'page a zero' => [['page' => '0'], 'Numéro de page invalide'],
            'page en texte' => [['page' => 'deux'], 'Numéro de page invalide'],
        ];
    }

    public function testDetailsSoiree(): void
    {
        $this->details->method('execute')->with(3)->willReturn(['id_soiree' => 3, 'spectacles' => []]);

        $reponse = $this->controller->detailsSoiree($this->requete(), $this->reponse(), ['id' => '3']);

        $this->assertSame(200, $reponse->getStatusCode());
        $this->assertSame(3, $this->json($reponse)['data']['id_soiree']);
    }

    public function testDetailsSoireeIntrouvable(): void
    {
        $this->details->method('execute')->willReturn(null);

        $reponse = $this->controller->detailsSoiree($this->requete(), $this->reponse(), ['id' => '999']);

        $this->assertSame(404, $reponse->getStatusCode());
    }

    public function testDetailsSoireeIdInvalide(): void
    {
        $this->details->expects($this->never())->method('execute');

        foreach (['abc', '0', '-1', '1.5'] as $id) {
            $reponse = $this->controller->detailsSoiree($this->requete(), $this->reponse(), ['id' => $id]);
            $this->assertSame(400, $reponse->getStatusCode(), "id = $id");
        }
    }
}
