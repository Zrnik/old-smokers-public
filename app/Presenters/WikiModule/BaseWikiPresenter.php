<?php


namespace App\Presenters\WikiModule;


use App\Presenters\BasePresenter;
use JetBrains\PhpStorm\Pure;
use Nette\Application\UI\InvalidLinkException;
use Zrnik\Cruip\MenuRenderer\Theme;
use Zrnik\Menu\Menu;

class BaseWikiPresenter extends BasePresenter
{
    /**
     * @throws InvalidLinkException
     */
    protected function startup(): void
    {
        parent::startup();
        $this->setLayout(__DIR__ . '/../../Templates/WikiModule/@layout.latte');
    }


    /**
     * @return array<string>
     */
    public function formatTemplateFiles(): array
    {
        $result = parent::formatTemplateFiles();

        foreach ($result as $key => $oldValue) {

            $wikiPresenter = explode(":", $this->getRequest()?->getPresenterName()??'Homepage')[1];

            $newValue = str_replace(
                sprintf("/Presenters/Templates/%s/", $wikiPresenter),
                sprintf("/Templates/WikiModule/%s/", $wikiPresenter),
                $oldValue
            );

            //bdump($oldValue);
            //bdump($newValue);

            $result[$key] = $newValue;
        }


        return $result;
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
            $this->linkGenerator->link("Wiki:Homepage:default")
        );


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

        $theme->desktopLinkStyle = 'text-gray-800 hover:text-gray-900';
        $theme->mobileLinkStyle = 'text-gray-800 hover:text-gray-900';

        return $theme;
    }

}
