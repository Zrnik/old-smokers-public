<?php


namespace App\Controls\Application;


use App\Model\Applications\ApplicationRepository;
use App\Model\Discord\Connection\Guilds;
use App\Model\Discord\Connection\Messages;
use App\Model\Security\DiscordUser;
use App\Model\Security\DiscordUserRepository;
use Nette\Application\LinkGenerator;

class CommentFormFactory
{

    public function __construct(
        private DiscordUser $user,
        private Guilds $guilds,
        private ApplicationRepository $applicationRepository,
        private Messages $messages, private LinkGenerator $linkGenerator,
        private DiscordUserRepository $discordUserRepository
    )
    {}

    public function create(int $applicationId): CommentForm
    {
        return new CommentForm(
            $applicationId, $this->user, $this->guilds, $this->applicationRepository,
            $this->messages, $this->linkGenerator, $this->discordUserRepository
        );
    }

}
