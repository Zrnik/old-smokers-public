<?php


namespace App\Controls\Application;


use App\Model\Applications\ApplicationRepository;
use App\Model\Discord\Connection\Guilds;
use App\Model\Discord\Connection\Messages;
use App\Model\Security\DiscordUser;
use App\Model\Security\DiscordUserRepository;
use Nette\Application\LinkGenerator;

class ApplicationFormFactory
{
    public function __construct(
        private ApplicationRepository $applicationRepository,
        private DiscordUserRepository $discordUserRepository,
        private Messages $messages, private DiscordUser $discordUser,
        private LinkGenerator $linkGenerator,  private Guilds $guilds
    )
    {
    }

    public function create(): ApplicationForm
    {
        return new ApplicationForm(
            $this->applicationRepository,
            $this->discordUserRepository,
            $this->messages, $this->discordUser,
            $this->linkGenerator, $this->guilds
        );
    }

}
