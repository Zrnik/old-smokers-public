<?php


namespace App\Controls\ScreenShots;


use App\Misc\FormRenderer;
use App\Model\ScreenShots\ScreenShotRepository;
use App\Model\Security\DiscordUser;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\TextArea;
use Nette\Forms\Controls\TextInput;
use Nette\Forms\Controls\UploadControl;
use Nette\Http\FileUpload;
use Nette\Utils\Image;
use Nette\Utils\ImageException;
use Nette\Utils\UnknownImageFileException;

class ScreenshotCommentForm extends Form
{
    private TextArea $text;

    public function __construct(
        private DiscordUser $user,
        private ScreenShotRepository $screenShotRepository,
        private int $screenshotId
    )
    {
        parent::__construct();
        $this->configure();
        $this->onSuccess[] = [$this, "save"];
    }

    private function configure(): void
    {
        $this->setRenderer(new FormRenderer());
        $this->text = $this->addTextArea("title", "Komentář")->setRequired();
        $this->addSubmit("send", "Přidat komentář!");
    }

    public function save(): void
    {
        if(!$this->user->isLoggedIn())
            return;

        $presenter = $this->getPresenter();

        if ($presenter === null)
            return;

        $this->screenShotRepository->createComment(
            $this->user, $this->screenshotId,  $this->text->getValue()
        );

        $presenter->flashMessage("Komentář přidán!", "bg-green-300 text-green-700");
        $presenter->redirect("Screens:detail", ["id" => $this->screenshotId]);


    }


}
