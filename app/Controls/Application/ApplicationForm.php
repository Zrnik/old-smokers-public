<?php


namespace App\Controls\Application;


use App\Misc\FormRenderer;
use App\Model\Applications\Application;
use App\Model\Applications\ApplicationRepository;
use App\Model\Discord\Connection\Guilds;
use App\Model\Discord\Connection\Messages;
use App\Model\Discord\Exceptions\MessageException;
use App\Model\Security\DiscordUser;
use App\Model\Security\DiscordUserRepository;
use Brick\DateTime\LocalDateTime;
use Brick\DateTime\TimeZone;
use JetBrains\PhpStorm\NoReturn;
use Nette\Application\AbortException;
use Nette\Application\LinkGenerator;
use Nette\Application\UI\Form;
use Nette\Application\UI\InvalidLinkException;
use Nette\Database\UniqueConstraintViolationException;
use Nette\Forms\Controls\SubmitButton;
use Nette\Forms\Controls\TextArea;
use Nette\Utils\Html;
use Nette\Utils\JsonException;
use Throwable;

class ApplicationForm extends Form
{
    private TextArea $applicationText;
    private SubmitButton $previewButton;
    private SubmitButton $submitButton;

    public function __construct(
        private ApplicationRepository $applicationRepository,
        private DiscordUserRepository $discordUserRepository,
        private Messages $messages, private DiscordUser $discordUser,
        private LinkGenerator $linkGenerator, private Guilds $guilds
    )
    {
        parent::__construct();

        $this->setRenderer(new FormRenderer());
        $this->applicationText = $this->addTextArea("applicationText", "Text vaší přihlášky:");
        $this->applicationText->setRequired();
        $this->applicationText->getControlPrototype()->setAttribute("rows", 15);
        $this->applicationText->setOption("description", "Můžete využít Markdown, viz odkaz výše.");

        $this->previewButton = $this->addSubmit("preview", "Ukázat Náhled");
        $this->previewButton->controlPrototype->class("bg-purple-300 hover:bg-purple-400 text-purple-700");
        $this->previewButton->onClick[] = [$this, 'preview'];

        $this->submitButton = $this->addSubmit("create", "Vytvořit Přihlášku");
        $this->submitButton->onClick[] = [$this, 'createApplication'];
    }

    /**
     * @throws AbortException
     * @throws JsonException
     * @throws Throwable
     */
    #[NoReturn]
    public function createApplication(): void
    {
        $application = Application::new();

        $voteAbleGuildmembers = 0;
        foreach ($this->guilds->getGuildMembers() as $gmember)
            if ($gmember->canVote())
                $voteAbleGuildmembers++;

        $nadpolovicniVetsina = intval(floor($voteAbleGuildmembers / 2) + 1);

        $application->ownerId = $this->discordUser->identity->getId();
        $application->text = strval($this->applicationText->getValue());

        $application->votesRequired = $nadpolovicniVetsina;

        $application->notifications = true;

        try {
            $this->applicationRepository->save($application);
        } catch (UniqueConstraintViolationException) {
            $this->addError("Nejspíš už nějakou přihlášku máte...");
            return;
        }

        try {

            $userLink = $this->discordUserRepository->getUserLink($this->discordUser->getId());

            $this->messages->sendNotification(
                sprintf(
                    "Na webu je nová přihláška od uživatele %s!\n%s",
                    $userLink,
                    $this->linkGenerator->link("Application:detail", ["id" => $application->id])
                )
            );
        } catch (MessageException $e) {
            $this->applicationRepository->delete($application);
        } catch (InvalidLinkException | Throwable $e) {
        }

        try {
            $this->messages->sendTo(
                $this->discordUser->getId(),
                $application->getGreetingsText()
            );
        } catch (MessageException $e) {

            //Týpek nemůže příjman zprávy, nejspíš protože není na
            //našem discordu, tak nastaváme notifikace na false
            $application->notifications = false;

            // a uložíme...
            $this->applicationRepository->save($application);
        }



        $this->getPresenter()?->redirect("Application:detail", ["id" => $application->id]);

    }


    public function preview(): void
    {
        $this->onRender[] = [$this, "displayPreview"];
    }

    public function displayPreview(): void
    {

        $previewApplication = Application::new();
        $previewApplication->text = strval($this->applicationText->getValue());

        echo Html::el("div")
            ->class("bg-gray-900 p-1 pb-2 mb-10")
            ->addHtml(
                Html::el("div")
                    ->class("h3")
                    ->setText("Tohle je Náhled:")
            )
            ->addHtml(
                Html::el("div")
                    ->class("markdown p-2 bg-gray-800")
                    ->setHtml($previewApplication->getMarkdown())
            )
            /**/
            ->render();
    }


}
