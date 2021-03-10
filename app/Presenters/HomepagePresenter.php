<?php

namespace App\Presenters;

use JetBrains\PhpStorm\NoReturn;
use Nette\Application\AbortException;
use Nette\DI\Attributes\Inject;
use Nette\DI\Container;
use Tracy\Debugger;

/**
 * Class HomepagePresenter
 * @package App\Presenters
 * @property HomepageTemplate $template
 */
class HomepagePresenter extends BasePresenter
{
    #[Inject]
    public Container $container;

    private function downloadCountLocation(): string
    {
        return Debugger::$logDirectory.'/dualDownloads.txt';
    }

    private function downloadCount(): int
    {
        if(file_exists($this->downloadCountLocation()))
            return intval(file_get_contents(
                "nette.safe://".$this->downloadCountLocation()
            ));

        return 0;
    }

    private function downloadAdd(): void
    {
        if(!file_exists(dirname($this->downloadCountLocation())))
            @mkdir(dirname($this->downloadCountLocation()),0777,true);

        file_put_contents(
            "nette.safe://".$this->downloadCountLocation(),
            $this->downloadCount() + 1
        );
    }

    public function renderDualClient(): void
    {
        $this->template->dualClientDownloadCount = $this->downloadCount();
    }

    /**
     * @throws AbortException
     */
    #[NoReturn] public function handleDownload(): void
    {
        $realLink = 'https://static.zrnik.eu/data/endor/dual/endor-launcher.zip';
        $this->downloadAdd();
        $this->redirectUrl($realLink);
    }

}
