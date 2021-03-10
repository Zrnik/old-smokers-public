<?php


namespace App\Presenters;

use App\Misc\FlashMock;
use App\Model\Security\DiscordUser;
use Contributte\Webpack\AssetLocator;
use Nette\Application\UI\Presenter;
use Nette\Bridges\ApplicationLatte\Template;
use Zrnik\Menu\Menu;

/**
 * Class BaseTemplate
 * @package App\Presenters
 */
class BaseTemplate extends Template
{
    /**
     * @var FlashMock[]
     */
    public array $flashes;

    public Menu $menu;

    public DiscordUser $user;

    public Presenter $presenter;

    public string $title;

    public string $image;

    public string $description;

    public AssetLocator $assetLocator;

    public string $version;
}
