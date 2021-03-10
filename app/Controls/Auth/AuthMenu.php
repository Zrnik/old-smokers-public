<?php


namespace App\Controls\Auth;

use App\Model\Discord\Connection\Guilds;
use App\Model\Discord\Guilds\GuildMember;
use App\Model\Security\DiscordIdentity;
use App\Model\Security\DiscordUser;
use Contributte\Webpack\AssetLocator;
use Nette\Application\LinkGenerator;
use Nette\Application\UI\Control;
use Nette\Application\UI\InvalidLinkException;
use Nette\Utils\Html;
use Throwable;

class AuthMenu extends Control
{

    private ?GuildMember $guildMember;

    /**
     * AuthMenu constructor.
     * @param DiscordUser $user
     * @param Guilds $guilds
     * @param AssetLocator $assetLocator
     * @param LinkGenerator $linkGenerator
     * @throws Throwable
     */
    public function __construct(
        private DiscordUser $user,
        private Guilds $guilds,
        private AssetLocator $assetLocator,
        private LinkGenerator $linkGenerator
    )
    {


        $this->guildMember = $this->guilds->getMember($user->getId()??-1);

    }

    /**
     * @throws InvalidLinkException
     */
    public function render(): void
    {
        if ($this->user->isLoggedIn()) {
            echo $this->userData($this->user->identity)->render();
        } else {
            echo $this->loginButton()->render();
        }
    }

    /**
     * @return Html<mixed>
     * @throws InvalidLinkException
     */
    private function loginButton(): Html
    {
        $html = Html::el();

        $presenter = $this->getPresenter();
        if($presenter === null)
            return $html;

        $html->addHtml(
            Html::el("a")
                ->href($this->linkGenerator->link("Auth:signIn", [
                    "state" => $presenter->storeRequest()
                ])??'')
                ->addHtml(
                    Html::el("img")
                        ->src(
                            $this->assetLocator
                                ->locateInPublicPath("images/discord.svg")
                        )
                        ->class(
                            "w-10 inline border-r-2 border-gray-700 pr-2 mr-3"
                        )
                )
                ->addText("Přihlásit se")
                ->class(
                    "btn btn-sm bg-discord text-white hover:text-gray-700 pl-2"
                )
        );


        return $html;
    }

    /**
     * @param DiscordIdentity $identity
     * @return Html<mixed>
     * @throws InvalidLinkException
     */
    private function userData(DiscordIdentity $identity): Html
    {
        $html = new Html();

        $presenter = $this->getPresenter();
        if($presenter === null)
            return $html;


        $html->addHtml(
            Html::el("div")
                ->addHtml(
                    Html::el("img")
                        ->src(
                            $identity->getData()->getAvatarUrl()
                        )
                        ->class(
                            "w-10 inline border-r-2 border-gray-500 pr-2 mr-3"
                        )
                )
                ->addHtml(
                    Html::el("a")
                        ->href(
                            $this->linkGenerator->link(
                                "Profile:",
                                ["state" => $presenter->storeRequest()]
                            )??''
                        )
                        ->class(
                            "inline border-r-2 border-gray-500 pr-2 mr-3"
                        )
                        ->style("color", $this->guildMember?->getHexColor()??"#ccc")
                    ->setText( $this->guildMember?->getNickname()??$this->user->getUsername())

                )
                ->addHtml(
                    Html::el("a")
                        ->href(
                            $this->linkGenerator->link(
                                "Auth:signOut",
                                ["state" => $presenter->storeRequest()]
                            )??''
                        )
                        ->setText("Odhlásit se")
                )
                ->class(
                    "btn btn-sm bg-gray-700 pl-2"
                )
        );

        return $html;
    }

}
