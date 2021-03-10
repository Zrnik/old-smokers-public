<?php


namespace App\Controls\ScreenShots;


use App\Misc\FormRenderer;
use App\Model\Discord\Connection\Messages;
use App\Model\Discord\Exceptions\MessageException;
use App\Model\ScreenShots\ScreenShotRepository;
use App\Model\Security\DiscordUser;
use Nette\Application\AbortException;
use Nette\Application\UI\Form;
use Nette\Application\UI\InvalidLinkException;
use Nette\Forms\Controls\TextInput;
use Nette\Forms\Controls\UploadControl;
use Nette\Http\FileUpload;
use Nette\Utils\Image;
use Nette\Utils\ImageException;
use Nette\Utils\UnknownImageFileException;

class ScreenshotUploadForm extends Form
{

    private TextInput $title;
    private UploadControl $file;

    public function __construct(
        private DiscordUser $user,
        private ScreenShotRepository $screenShotRepository,
        private Messages $messages
    )
    {
        parent::__construct();
        $this->configure();
        $this->onSuccess[] = [$this, "save"];
    }

    private function configure(): void
    {
        $this->setRenderer(new FormRenderer());
        $this->title = $this->addText("title", "Název obrázku")->setRequired();
        $this->file = $this->addUpload("picture", "Obrázek")->setRequired();
        $this->addSubmit("send", "Přidat obrázek!");
    }

    /**
     * @throws MessageException
     * @throws AbortException
     * @throws InvalidLinkException
     */
    public function save(): void
    {
        $presenter = $this->getPresenter();

        if ($presenter === null)
            return;

        /**
         * @var FileUpload $fileUpload
         */
        $fileUpload =  $this->file->getValue();

        if(!$fileUpload->isOk())
        {
            $this->file->addError("Něco se pokazilo!");
            return;
        }

        if(!$fileUpload->isImage())
        {
            $this->file->addError("Tohle nevypadá jako obrázek!");
            return;
        }

        $newId = $this->screenShotRepository->create(
            $this->user, $this->title->getValue(), $fileUpload
        );

        $this->messages->sendNotification(
            sprintf("Na webu je nový screenshot!\n\n`%s`\n\n", $this->title->getValue()).
            $presenter->link("//Screens:detail",["id"=>$newId])
        );

        $presenter->flashMessage("Obrázek přidán!", "bg-green-300 text-green-700");
        $presenter->redirect("Screens:detail", ["id" => $newId]);


    }


}
