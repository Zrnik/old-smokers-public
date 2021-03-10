<?php


namespace App\Misc;


use Nette\Forms\Control;
use Nette\Forms\Controls\BaseControl;
use Nette\Forms\IControl;
use Nette\Forms\Rendering\DefaultFormRenderer;
use Nette\Utils\Html;

class FormRenderer extends DefaultFormRenderer
{

    public function renderBegin(): string
    {
        $this->wrappers["controls"]["container"] =
            Html::el("div");

        $this->wrappers["pair"]["container"] =
            Html::el("div")
                ->class("flex flex-wrap -mx-3 mb-4");


        $this->wrappers["control"]["container"] =
            Html::el("div")
                ->class("w-full");

        //bdump($this->wrappers);
        $this->wrappers["control"][".submit"]
            = 'btn bg-green-300 hover:bg-green-400 text-green-700';

        $this->wrappers["label"]["container"] =
            Html::el("div")
                ->class("w-full");

        $this->wrappers["error"]["container"] = Html::el("div")
            ->class("flex flex-wrap -mx-3 mb-1 required");

        $this->wrappers["error"]["item"] = Html::el("div")
            ->class(" rounded border px-3 py-3 mb-3 w-full bg-red-300 text-red-900 border-red-900");

        $this->wrappers["control"][".error"] = "bg-red-300 rounded-b-none";

        $this->wrappers["control"]["errorcontainer"] =
            Html::el("div")
                ->class(" rounded border px-3 py-3 mb-3 w-full bg-red-300 text-red-900 border-red-900" . ' rounded-t-none');

        $this->wrappers["control"]["erroritem"] =
            Html::el("div");

        // bdump($this->wrappers);

        return parent::renderBegin();
    }


    /**
     * @param IControl $control
     * @return Html<Html>
     */
    public function renderControl(Control $control): Html
    {

        if ($control instanceof BaseControl) {
            /**
             * @var BaseControl $formControl
             */
            $formControl = $control;

            // $formControl->control->class();
            $formControl->setHtmlAttribute(
                "class",
                "form-input w-full text-gray-400 border-gray-500"
            );

            $formControl->setOption("class", "");

        }

        return parent::renderControl($control);
    }

}
