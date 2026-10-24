<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use App\adapters\http\UtilisateurController;
use App\application\ports\api\AuthentifierUtilisateurInterface;
use App\application\ports\api\ChangerMotDePasseInterface;
use App\application\ports\api\InscrireUtilisateurInterface;
use App\application\ports\api\ModifierProfilInterface;
use App\application\ports\api\ObtenirProfilInterface;
use DomainException;
use PDOException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;

final class UtilisateurControllerTest extends HttpTestCase
{
    private InscrireUtilisateurInterface&MockObject $inscrire;
    private AuthentifierUtilisateurInterface&MockObject $authentifier;
    private ObtenirProfilInterface&MockObject $obtenirProfil;
    private ModifierProfilInterface&MockObject $modifierProfil;
    private ChangerMotDePasseInterface&MockObject $changerMotDePasse;
    private UtilisateurController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->inscrire = $this->createMock(InscrireUtilisateurInterface::class);
        $this->authentifier = $this->createMock(AuthentifierUtilisateurInterface::class);
        $this->obtenirProfil = $this->createMock(ObtenirProfilInterface::class);
        $this->modifierProfil = $this->createMock(ModifierProfilInterface::class);
        $this->changerMotDePasse = $this->createMock(ChangerMotDePasseInterface::class);

        $this->controller = new UtilisateurController(
            $this->inscrire,
            $this->authentifier,
            $this->obtenirProfil,
            $this->modifierProfil,
            $this->changerMotDePasse
        );
    }

    private function inscription(): array
    {
        return ['nom' => 'Dupont', 'prenom' => 'Marie', 'email' => 'marie@exemple.fr', 'password' => 'motdepasse123'];
    }

    public function testInscriptionReussie(): void
    {
        $this->inscrire->method('execute')->willReturn(12);

        $reponse = $this->controller->inscrire($this->requete('POST', [], $this->inscription()), $this->reponse());

        $this->assertSame(201, $reponse->getStatusCode());
        $this->assertSame(['data' => ['id_utilisateur' => 12]], $this->json($reponse));
    }

    public function testInscriptionIncomplete(): void
    {
        $this->inscrire->expects($this->never())->method('execute');

        $body = $this->inscription();
        unset($body['email']);

        $reponse = $this->controller->inscrire($this->requete('POST', [], $body), $this->reponse());

        $this->assertSame(400, $reponse->getStatusCode());
    }

    public function testInscriptionAvecUnChampQuiNestPasUnTexte(): void
    {
        $body = $this->inscription();
        $body['nom'] = ['Dupont'];

        $reponse = $this->controller->inscrire($this->requete('POST', [], $body), $this->reponse());

        $this->assertSame(400, $reponse->getStatusCode());
    }

    #[DataProvider('erreursInscription')]
    public function testErreursMetierInscription(string $codeErreur, int $statut): void
    {
        $this->inscrire->method('execute')->willThrowException(new DomainException($codeErreur));

        $reponse = $this->controller->inscrire($this->requete('POST', [], $this->inscription()), $this->reponse());

        $this->assertSame($statut, $reponse->getStatusCode());
    }

    public static function erreursInscription(): array
    {
        return [
            'email deja pris' => ['email_deja_utilise', 409],
            'email invalide' => ['email_invalide', 400],
            'mot de passe invalide' => ['mot_de_passe_invalide', 400],
            'nom invalide' => ['nom_prenom_invalides', 400],
        ];
    }

    public function testInscriptionDoublonDetecteParLaBase(): void
    {
        // deux inscriptions en meme temps : c'est la contrainte unique de MySQL qui bloque
        $exception = new PDOException('Duplicate entry');
        $reflexion = new \ReflectionProperty(\Exception::class, 'code');
        $reflexion->setValue($exception, '23000');

        $this->inscrire->method('execute')->willThrowException($exception);

        $reponse = $this->controller->inscrire($this->requete('POST', [], $this->inscription()), $this->reponse());

        $this->assertSame(409, $reponse->getStatusCode());
    }

    public function testConnexionSansIdentifiants(): void
    {
        $this->authentifier->expects($this->never())->method('execute');

        $reponse = $this->controller->connexion($this->requete('POST', [], ['email' => 'marie@exemple.fr']), $this->reponse());

        $this->assertSame(400, $reponse->getStatusCode());
    }

    public function testConnexionRefusee(): void
    {
        $this->authentifier->method('execute')->willReturn(null);

        $reponse = $this->controller->connexion(
            $this->requete('POST', [], ['email' => 'marie@exemple.fr', 'password' => 'mauvais']),
            $this->reponse()
        );

        $this->assertSame(401, $reponse->getStatusCode());
        $this->assertArrayNotHasKey('user_id', $_SESSION);
    }

    public function testRoutesProtegeesSansConnexion(): void
    {
        $profil = $this->controller->profil($this->requete(), $this->reponse());
        $modif = $this->controller->modifierProfil($this->requete('PATCH', [], ['nom' => 'X']), $this->reponse());
        $mdp = $this->controller->modifierMotDePasse($this->requete('PATCH', [], []), $this->reponse());

        $this->assertSame(401, $profil->getStatusCode());
        $this->assertSame(401, $modif->getStatusCode());
        $this->assertSame(401, $mdp->getStatusCode());
    }

    public function testProfilConnecte(): void
    {
        $this->connecter(5);
        $this->obtenirProfil->method('execute')->with(5)->willReturn(['id_utilisateur' => 5, 'nom' => 'Dupont']);

        $reponse = $this->controller->profil($this->requete(), $this->reponse());

        $this->assertSame(200, $reponse->getStatusCode());
        $this->assertSame('Dupont', $this->json($reponse)['data']['nom']);
    }

    public function testProfilDUnCompteSupprime(): void
    {
        $this->connecter(5);
        $this->obtenirProfil->method('execute')->willReturn(null);

        $this->assertSame(404, $this->controller->profil($this->requete(), $this->reponse())->getStatusCode());
    }

    public function testModifierProfilRenvoieLeProfilAJour(): void
    {
        $this->connecter(5);
        $this->modifierProfil->expects($this->once())
            ->method('execute')
            ->with(5, 'Martin', 'Paul', 'paul@exemple.fr');
        $this->obtenirProfil->method('execute')->willReturn(['id_utilisateur' => 5, 'nom' => 'Martin']);

        $reponse = $this->controller->modifierProfil(
            $this->requete('PATCH', [], ['nom' => 'Martin', 'prenom' => 'Paul', 'email' => 'paul@exemple.fr']),
            $this->reponse()
        );

        $this->assertSame(200, $reponse->getStatusCode());
        $this->assertSame('Martin', $this->json($reponse)['data']['nom']);
    }

    public function testModifierProfilIncomplet(): void
    {
        $this->connecter(5);
        $this->modifierProfil->expects($this->never())->method('execute');

        $reponse = $this->controller->modifierProfil(
            $this->requete('PATCH', [], ['nom' => 'Martin']),
            $this->reponse()
        );

        $this->assertSame(400, $reponse->getStatusCode());
    }

    public function testModifierProfilEmailInvalide(): void
    {
        $this->connecter(5);
        $this->modifierProfil->method('execute')->willThrowException(new DomainException('email_invalide'));

        $reponse = $this->controller->modifierProfil(
            $this->requete('PATCH', [], ['nom' => 'Martin', 'prenom' => 'Paul', 'email' => 'nimporte']),
            $this->reponse()
        );

        $this->assertSame(400, $reponse->getStatusCode());
        $this->assertSame('Adresse email invalide', $this->json($reponse)['error']);
    }

    public function testModifierMotDePasseActuelIncorrect(): void
    {
        $this->connecter(5);
        $this->changerMotDePasse->method('execute')->willReturn(false);

        $reponse = $this->controller->modifierMotDePasse(
            $this->requete('PATCH', [], ['current_password' => 'faux', 'new_password' => 'nouveaumdp123']),
            $this->reponse()
        );

        $this->assertSame(400, $reponse->getStatusCode());
        $this->assertSame('Mot de passe actuel incorrect', $this->json($reponse)['error']);
    }

    public function testModifierMotDePasseTropCourt(): void
    {
        $this->connecter(5);
        $this->changerMotDePasse->method('execute')->willThrowException(new DomainException('mot_de_passe_invalide'));

        $reponse = $this->controller->modifierMotDePasse(
            $this->requete('PATCH', [], ['current_password' => 'ancienmdp123', 'new_password' => 'court']),
            $this->reponse()
        );

        $this->assertSame(400, $reponse->getStatusCode());
    }

    public function testModifierMotDePasseChampsManquants(): void
    {
        $this->connecter(5);
        $this->changerMotDePasse->expects($this->never())->method('execute');

        $reponse = $this->controller->modifierMotDePasse(
            $this->requete('PATCH', [], ['new_password' => 'nouveaumdp123']),
            $this->reponse()
        );

        $this->assertSame(400, $reponse->getStatusCode());
    }
}
