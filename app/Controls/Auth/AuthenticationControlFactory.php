<?php


namespace App\Controls\Auth;


use App\Model\Authentication\DiscordFacade;
use App\Model\Authentication\User;
use App\Model\Discord\Connection\Guilds;
use App\Model\Security\DiscordUser;
use Contributte\Webpack\AssetLocator;
use JetBrains\PhpStorm\Pure;
use Nette\Application\LinkGenerator;
use Throwable;

class AuthenticationControlFactory
{
    public function __construct(
        private DiscordUser $user,
        private Guilds $guilds,
        private AssetLocator $assetLocator,
        private LinkGenerator $linkGenerator
    )
    {
    }

    /**
     * @return AuthMenu
     * @throws Throwable
     */
    public function create(): AuthMenu
    {
        return new AuthMenu(
            $this->user, $this->guilds, $this->assetLocator, $this->linkGenerator
        );
    }
}
