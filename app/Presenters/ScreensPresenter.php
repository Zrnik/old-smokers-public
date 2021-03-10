<?php


namespace App\Presenters;

use App\Controls\ScreenShots\CommentListDataGrid;
use App\Controls\ScreenShots\CommentListDataGridFactory;
use App\Controls\ScreenShots\ScreenshotCommentForm;
use App\Controls\ScreenShots\ScreenshotCommentFormFactory;
use App\Controls\ScreenShots\ScreenshotUploadFactory;
use App\Controls\ScreenShots\ScreenshotUploadForm;
use App\Model\ScreenShots\ScreenShotRepository;
use Nette\Application\AbortException;
use Nette\Application\Attributes\Persistent;
use Nette\Application\BadRequestException;
use Nette\Application\UI\InvalidLinkException;
use Nette\DI\Attributes\Inject;
use Nette\Utils\Paginator;

/**
 * Class ScreensPresenter
 * @package App\Presenters
 * @property ScreensTemplate $template
 */
class ScreensPresenter extends BasePresenter
{
    #[Inject]
    public ScreenShotRepository $screenShotRepository;

    #[Inject]
    public ScreenshotUploadFactory $screenshotUploadFactory;

    #[Inject]
    public ScreenshotCommentFormFactory $screenshotCommentFormFactory;

    #[Inject]
    public CommentListDataGridFactory $commentListDataGridFactory;

    #[Persistent]
    public int $id = 0;

    #[Persistent]
    public int $page = 1;

    protected function beforeRender()
    {
        parent::beforeRender();
        $this->template->screenShotRepository = $this->screenShotRepository;
    }

    public function renderDefault(): void
    {
        $this->id = 0;

        $paginator = new Paginator();

        $paginator->setItemCount($this->screenShotRepository->count());
        $paginator->setPage($this->page);
        $paginator->setItemsPerPage(12);

        $this->template->paginator = $paginator;

        $this->template->images = $this->screenShotRepository->getImages($paginator);
        $this->template->latestComments = $this->screenShotRepository->getLatestComments();
    }

    /**
     * @throws BadRequestException
     */
    public function renderDetail(): void
    {
        $image = $this->screenShotRepository->getImage($this->id);

        if ($image === null) {
            $this->error(sprintf("No image with id '%s' found!", $this->id));
            return;
        }

        $this->template->detailedImagePage = $this->screenShotRepository->getPageOf($image, 12);

        $this->page = 1;
        $this->template->detailedImage = $image;

        $this->screenShotRepository->increaseViews($image->id??0);


    }


    /**
     * @throws BadRequestException
     */
    public function renderDelete(): void
    {
        if (!$this->user->hasRole("administrator"))
            return;

        $image = $this->screenShotRepository->getImage($this->id);

        if ($image === null) {
            $this->error(sprintf("No image with id '%s' found!", $this->id));
            return;
        }

        $this->template->deleteImage = $image;


    }

    /**
     * @throws AbortException
     * @throws InvalidLinkException
     * @throws BadRequestException
     */
    public function handleDeleteImage(): void
    {
        if (!$this->user->hasRole("administrator"))
            return;

        $image =  $this->screenShotRepository->getImage($this->id);

        if($image === null)
        {
            $this->error("No image!");
            return;
        }

        $redirectUrl = $this->link(
            "Screens:default",
            [
                "page" => $this->screenShotRepository->getPageOf(
                    $image,
                    12
                )
            ]
        );

        $this->screenShotRepository->remove($this->id);
        $this->flashMessage("Obrázek smazán!");
        $this->redirectUrl($redirectUrl);




        //
    }


    public function renderComments(): void
    {
        $this->id = 0;
    }

    /**
     * @throws BadRequestException
     */
    public function renderUpload(): void
    {
        if (!$this->user->isLoggedIn())
            $this->error("Only signed in users can upload screenshots!");
    }

    public function createComponentUploadForm(): ScreenshotUploadForm
    {
        return $this->screenshotUploadFactory->create();
    }

    public function createComponentCommentForm(): ScreenshotCommentForm
    {
        return $this->screenshotCommentFormFactory->create($this->id);
    }

    public function createComponentCommentGrid(): CommentListDataGrid
    {
        return $this->commentListDataGridFactory->create();
    }


}
