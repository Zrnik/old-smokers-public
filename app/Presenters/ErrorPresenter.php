<?php


namespace App\Presenters;


use Nette\Application\BadRequestException;
use Nette\Application\Helpers;
use Nette\Application\IPresenter;
use Nette\Application\Request;
use Nette\Application\Response;
use Nette\Application\Responses\CallbackResponse;
use Nette\Application\Responses\ForwardResponse;
use Nette\Http\IRequest;
use Nette\SmartObject;
use Tracy\ILogger;

final class ErrorPresenter implements IPresenter
{
    use SmartObject;

    private ILogger $logger;

    public function __construct(ILogger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function run(Request $request): Response
    {
        $e = $request->getParameter('exception');

        if ($e instanceof BadRequestException) {
            // $this->logger->log("HTTP code {$e->getCode()}: {$e->getMessage()} in {$e->getFile()}:{$e->getLine()}", 'access');
            [$module, , $sep] = Helpers::splitName($request->getPresenterName());
            $errorPresenter = $module . $sep . 'Error4xx';
            return new ForwardResponse($request->setPresenterName($errorPresenter));
        }

        $this->logger->log($e, ILogger::EXCEPTION);


        return new CallbackResponse(
            function (\Nette\Http\IRequest $httpRequest, \Nette\Http\Response $httpResponse): void {
            if (preg_match('#^text/html(?:;|$)#', (string) $httpResponse->getHeader('Content-Type'))) {
                require __DIR__ . '/../Templates/Error/500.phtml';
            }
        });
    }
}
