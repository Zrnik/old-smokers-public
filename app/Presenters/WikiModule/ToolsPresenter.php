<?php


namespace App\Presenters\WikiModule;


use App\Bootstrap;
use App\Model\UltimaOnline\Text\TextRenderer;
use Nette\Utils\Image;

class ToolsPresenter extends BaseWikiPresenter
{



    public function renderUoText(): void
    {
        Bootstrap::cors();
        $string = $this->getParameter("string");

        if($string !== null)
            TextRenderer::render(
                $this->getParameter("string"),
                $this->getParameter("color")
            )->send(Image::PNG);

    }


}
