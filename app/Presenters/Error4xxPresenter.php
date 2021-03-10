<?php


namespace App\Presenters;


use Nette\Application\BadRequestException;
use Nette\Application\Request;
use Nette\Application\UI\InvalidLinkException;
use Tracy\Debugger;

/**
 * Class Error4xxPresenter
 * @package App\Presenters
 * @property Error4xxTemplate $template
 */
class Error4xxPresenter extends BasePresenter
{
    /**
     * @throws BadRequestException
     * @throws InvalidLinkException
     */
    public function startup(): void
    {
        parent::startup();
        if (!$this->getRequest()?->isMethod(Request::FORWARD) ?? false) {
            $this->error();
        }
    }


    public function renderDefault(BadRequestException $exception): void
    {
        $this->template->code = $exception->getCode();
        $this->template->reason = $exception->getMessage();

        Debugger::log($this->template->reason,"error-".$exception->getCode());

        // load template 403.latte or 404.latte or ... 4xx.latte
        $file = __DIR__ . "/../Templates/Error/{$exception->getCode()}.latte";
        $file = is_file($file) ? $file : __DIR__ . '/../Templates/Error/4xx.latte';
        $this->template->setFile($file);
    }
}
