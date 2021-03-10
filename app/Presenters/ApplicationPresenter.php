<?php


namespace App\Presenters;


use App\Controls\Application\ApplicationForm;
use App\Controls\Application\ApplicationFormFactory;
use App\Controls\Application\CommentForm;
use App\Controls\Application\CommentFormFactory;
use App\Controls\Application\VotingForm;
use App\Controls\Application\VotingFormFactory;
use App\Model\Applications\ApplicationRepository;
use App\Model\Discord\Connection\Guilds;
use App\Model\Discord\Connection\Messages;
use App\Model\Discord\Exceptions\MessageException;
use App\Model\Endor\CharacterRepository;
use App\Model\Security\DiscordUserRepository;
use JetBrains\PhpStorm\Pure;
use Nette\Application\AbortException;
use Nette\Application\Attributes\Persistent;
use Nette\Application\BadRequestException;
use Nette\DI\Attributes\Inject;
use Nette\Utils\JsonException;

/**
 * Class ApplicationPresenter
 * @package App\Presenters
 * @property ApplicationTemplate $template
 */
class ApplicationPresenter extends BasePresenter
{

    #[Inject]
    public ApplicationRepository $applicationRepository;

    #[Inject]
    public CharacterRepository $characterRepository;

    #[Inject]
    public ApplicationFormFactory $applicationFormFactory;

    #[Inject]
    public DiscordUserRepository $discordUserRepository;

    #[Inject]
    public VotingFormFactory $votingFormFactory;

    #[Inject]
    public Guilds $guilds;

    #[Inject]
    public Messages $messages;

    #[Inject]
    public CommentFormFactory $commentFormFactory;

    #[Persistent]
    public ?int $id = null;


    protected function createComponentApplicationForm(): ApplicationForm
    {
        return $this->applicationFormFactory->create();
    }

    public function renderDefault(): void
    {
        $this->template->applicationList = $this->applicationRepository->getList();
        $this->template->discordUserRepository = $this->discordUserRepository;
    }

    /**
     * @throws BadRequestException
     */
    public function renderDetail(): void
    {
        $this->template->guilds = $this->guilds;
        $this->template->discordUserRepository = $this->discordUserRepository;
        $application = $this->applicationRepository->getApplication($this->id);

        if ($application === null) {
            $this->error(
                sprintf("Přihláška s id '%s' neexistuje!", $this->id),
                404
            );
            return;
        }

        $this->template->application = $application;

        $user = $this->discordUserRepository->getUser(
            $application->ownerId
        );

        if ($user === null) {
            $this->error("Přihláška nenalezena, jelikož nemá majtele!", 404);
            return;
        }

        $this->template->applicationOwner = $user;

        $this->template->characters = $this->characterRepository->getCharactersOf(
            $application->ownerId ?? 0
        );

        $this->template->comments = $this->applicationRepository->getComments(
            $application->id ?? 0
        );
    }

    #[Pure]
    public function createComponentVotingForm(): VotingForm
    {
        return $this->votingFormFactory->create($this->id ?? 0);
    }

    protected function createComponentCommentForm(): CommentForm
    {
        return $this->commentFormFactory->create($this->id ?? 0);
    }

    /**
     * @throws JsonException
     * @throws AbortException
     */
    public function handleReconnectDiscord(): void
    {
        $application = $this->applicationRepository->getApplication($this->id);

        if ($application !== null) {
            try {
                $this->messages->sendTo($this->user->getId(), $application->getGreetingsText());
                $application->notifications = true;
                $this->applicationRepository->save($application);
                $this->flashMessage(
                    "Spojení bylo navázáno!",
                    "bg-green-300 text-green-700 border-green-700"
                );
                $this->redirect("this");

            } catch (MessageException) {
                $this->flashMessage(
                    "Spojení se nepodařilo navázat!",
                    "bg-red-300 text-red-700 border-red-700"
                );
            }

        }

    }
}
