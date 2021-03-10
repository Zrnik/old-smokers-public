<?php


namespace App\Presenters;


use App\Model\ScreenShots\Screen;
use App\Model\ScreenShots\ScreenComment;
use App\Model\ScreenShots\ScreenShotRepository;
use Nette\Utils\Paginator;

class ScreensTemplate extends BaseTemplate
{

    /**
     * @var Screen[]
     */
    public array $images;

    /**
     * @var ScreenComment[]
     */
    public array $latestComments;


    public Screen $detailedImage;

    public ScreenShotRepository $screenShotRepository;

    public Paginator $paginator;

    public int $detailedImagePage;

    /**
     * @var Screen
     */
    public Screen $deleteImage;


}
