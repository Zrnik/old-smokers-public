<?php

namespace App\Presenters;

use App\Controls\Auth\AuthenticationControlFactory;
use App\Controls\Auth\AuthMenu;
use App\Model\Security\DiscordUser;
use Contributte\Webpack\AssetLocator;
use JetBrains\PhpStorm\Pure;
use Nette\Application\LinkGenerator;
use Nette\Application\UI\InvalidLinkException;
use Nette\Application\UI\Presenter;
use Nette\DI\Attributes\Inject;
use Throwable;
use Zrnik\Cruip\MenuRenderer\DesktopMenu;
use Zrnik\Cruip\MenuRenderer\MobileMenu;
use Zrnik\Cruip\MenuRenderer\Services\CruipMenuFactory;
use Zrnik\Cruip\MenuRenderer\Theme;
use Zrnik\Menu\Menu;

/**
 * Class BasePresenter
 * @package App\Presenters
 * @property BaseTemplate $template
 * @property DiscordUser $user
 */
class BasePresenter extends Presenter
{
    #[Inject]
    public CruipMenuFactory $cruipMenuFactory;

    #[Inject]
    public LinkGenerator $linkGenerator;

    #[Inject]
    public AuthenticationControlFactory $authenticationControlFactory;

    #[Inject]
    public AssetLocator $assetLocator;


    protected function beforeRender()
    {
        //Tohle je defaultní description page, jednotlive stranky si ho upraví sami.
        $this->template->description =
            'Jsme nová guilda na Ultima Online Shardu Endor-Reborn! Nábor máme otevřen, navštivte nás na discordu!';

        //To samé platí pro obrázek (aby třeba screenshot zobrazil náhled screenu a né ten konopný list.
        $this->template->image = $this->assetLocator->locateInPublicPath('images/cannabis.svg');

        // Aby obrazky mohly najit lokaci ve WWWdi, injectneme si do template assetlocator
        $this->template->assetLocator = $this->assetLocator;

        parent::beforeRender(); // TODO: Change the autogenerated stub
    }


    /**
     * @return array<string>
     */
    public function formatTemplateFiles(): array
    {
        $result = parent::formatTemplateFiles();

        foreach ($result as $key => $value) {
            $value = str_replace("\\", "/", $value);

            // Upper case 'T' is mandatory on linux server
            $value = str_replace(
                "/templates/",
                "/Templates/",
                $value
            );

            $result[$key] = $value;
        }

        return $result;
    }

    /**
     * @throws InvalidLinkException
     */
    protected function startup(): void
    {
        $this->setLayout(__DIR__ . '/../Templates/@layout.latte');
        parent::startup();
        $this->template->menu = $this->createMenu();
    }

    /**
     * @return DesktopMenu
     * @throws InvalidLinkException
     * @throws Throwable
     */
    protected function createComponentMenuDesktop(): DesktopMenu
    {
        return $this->cruipMenuFactory->createDesktopMenu(
            $this->createMenu(),
            $this->authenticationControlFactory->create(),
            $this->getCruipMenuTheme()
        );
    }

    /**
     * @return MobileMenu
     * @throws InvalidLinkException
     * @throws Throwable
     */
    protected function createComponentMenuMobile(): MobileMenu
    {
        return $this->cruipMenuFactory->createMobileMenu(
            $this->createMenu(),
            $this->authenticationControlFactory->create(),
            $this->getCruipMenuTheme()
        );
    }

    /**
     * @param string $name
     * @return AuthMenu
     * @throws Throwable
     */
    public function createComponentAuthenticationControl(string $name): AuthMenu
    {
        return $this->authenticationControlFactory->create();
    }

    /**
     * @return Menu
     * @throws InvalidLinkException
     */
    protected function createMenu(): Menu
    {
        $menu = new Menu();

        $menu->addLink(
            "Domů",
            $this->linkGenerator->link("Homepage:default")
        );

        $menu->addLink(
            "Seznam Členů",
            $this->linkGenerator->link("Members:")
        );

        if ($this->user->isLoggedIn())
            $menu->addLink(
                "Nastavení",
                $this->linkGenerator->link("Profile:")
            );

        $menu->addLink(
            "Dual Client",
            $this->linkGenerator->link("Homepage:dualClient")
        );

        /* if($this->user->hasRole("administrator"))
         {*/
        $menu->addLink(
            "Screenshoty",
            $this->linkGenerator->link("Screens:")
        );
        //  }

        /*  $menu->addLink(
              "Přihlášky",
              $this->link("Application:")
          );*/

        /*

         if($this->user->hasRole("administrator"))
         {
             $menu->addLink(
                 "Informace",
                 $this->linkGenerator->link("Wiki:Homepage:default")
             );
         }*/


        return $menu;
    }

    /**
     * @return Theme
     */
    #[Pure]
    protected function getCruipMenuTheme(): Theme
    {
        $theme = new Theme();

        $theme->ajax = false;

        $theme->desktopLinkStyle = 'text-gray-300 hover:text-gray-400';
        $theme->mobileLinkStyle = 'text-gray-600 hover:text-gray-700';

        return $theme;
    }

}
