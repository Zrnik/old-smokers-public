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
use Nette\Application\LinkGenerator;
use Nette\Application\UI\Form;
use Nette\Application\UI\InvalidLinkException;
use Nette\Forms\Controls\TextArea;
use Nette\InvalidStateException;
use Nette\Utils\JsonException;

class CommentForm extends Form
{

    private TextArea $commentText;
    private Application $application;

    public function __construct(
        private int $applicationId, private DiscordUser $user,
        private Guilds $guilds,
        private ApplicationRepository $applicationRepository,
        private Messages $messages, private LinkGenerator $linkGenerator,
        private DiscordUserRepository $discordUserRepository
    )
    {
        $application = $this->applicationRepository
            ->getApplication($this->applicationId);

        if($application === null)
        {
            throw new InvalidStateException(
                "No application!"
            );
        }

        $this->application = $application;

        parent::__construct();
        $this->setRenderer(new FormRenderer());
        $this->commentText = $this->addTextArea(
            "text_of_comment", "Text komentáře"
        )->setRequired();
        $this->addSubmit("submit", "Přidat komentář!");

        $this->onSuccess[] = [$this, "addComment"];
    }

    /**
     * @throws InvalidLinkException
     * @throws MessageException
     * @throws JsonException
     * @throws \Nette\Application\AbortException
     */
    public function addComment(): void
    {

        $newCommentId = $this->applicationRepository->addComment(
            $this->applicationId, $this->user->getId(),
            $this->commentText->getValue()
        );

        if($this->application->ownerId !== $this->user->getId())
        {
            // Nepsal to majtel přihlášky,
            // informujeme ho že někdo odepsal!

            try {
                $this->messages->sendTo(
                    $this->application->ownerId,
                    sprintf(
                        "Uživatel %s přidal komentář k vaší přihlášce!\n%s",
                        $this->discordUserRepository->getUserLink($this->user->getId()),
                        $this->linkGenerator->link(
                            "Application:detail",["id"=>$this->applicationId]
                        )
                    )
                );
            } catch (MessageException $e) {
                // a nebo ne :)
            }

        }

        // Informujeme o tom Discord server!

        $this->messages->sendNotification(
            sprintf(
                "Uživatel %s přidal komentář k přihlášce uživatele %s!\n%s",
                $this->discordUserRepository->getUserLink($this->user->getId()),
                $this->discordUserRepository->getUserLink($this->application->ownerId),
                $this->linkGenerator->link(
                    "Application:detail",["id"=>$this->applicationId]
                )
            )
        );

        $presenter = $this->getPresenter();

        if($presenter !== null)
        {
            $presenter->flashMessage(
                "Komentář přidán!",
                "text-green-700 border-green-700 bg-green-300"
            );

            $presenter->redirect(
                "this#comment-".$newCommentId
            );
        }



    }
}
