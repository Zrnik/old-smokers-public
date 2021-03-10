<?php


namespace App\Presenters;


use App\Model\Authentication\DiscordFacade;
use App\Model\Discord\Connection\OAuth2;
use GuzzleHttp\Exception\GuzzleException;
use JetBrains\PhpStorm\NoReturn;
use Nette\Application\AbortException;
use Nette\Application\Attributes\Persistent;
use Nette\Application\UI\InvalidLinkException;
use Nette\DI\Attributes\Inject;
use Nette\Http\UrlScript;
use Nette\Security\AuthenticationException;
use Nette\Utils\JsonException;

class AuthPresenter extends BasePresenter
{
    #[Persistent]
    public string $code = "";

    #[Persistent]
    public string $state = "";

    #[Inject]
    public OAuth2 $discordOAuth;

    /**
     * @throws AbortException
     * @throws InvalidLinkException
     */
    #[NoReturn]
    public function actionSignIn(): void
    {
        /*var_dump( $this->link(
            "//Auth:signInCallback",
        ));
        die("Oops!");*/

        $this->redirectUrl(
            $this->discordOAuth->getSignInUrl(
                $this->link(
                    "//Auth:signInCallback",
                ),
                ["state" => $this->state]
            )
        );
    }

    /**
     * @throws AuthenticationException
     * @throws GuzzleException
     * @throws InvalidLinkException
     * @throws JsonException
     */
    public function actionSignInCallback(): void
    {
        $codeInfo = $this->discordOAuth->getCodeInfo(
            $this->code,
            (new UrlScript(
                $this->link("//Auth:signInCallback")
            ))
        );


        $this->user->login($codeInfo["access_token"]);
        $this->restoreRequest($this->state);
    }

    public function actionSignOut(): void
    {
        $this->user->logout();
        $this->restoreRequest($this->state);
    }

}
