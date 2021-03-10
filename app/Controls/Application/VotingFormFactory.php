<?php


namespace App\Controls\Application;


use App\Model\Applications\ApplicationRepository;
use App\Model\Discord\Connection\Guilds;
use App\Model\Discord\Connection\Messages;
use App\Model\Security\DiscordUser;
use App\Model\Security\DiscordUserRepository;
use JetBrains\PhpStorm\Pure;

/**
 * Class VotingFormFactory
 * @package App\Controls\Application
 */
class VotingFormFactory
{
    public function __construct(
        private DiscordUser $user,
        private ApplicationRepository $applicationRepository,
        private Guilds $guilds, private Messages $messages,
        private DiscordUserRepository $discordUserRepository
    )
    {
    }

    #[Pure]
    public function create(int $applicationId): VotingForm
    {
        return new VotingForm(
            $applicationId, $this->user,
            $this->guilds, $this->messages,
            $this->applicationRepository,
            $this->discordUserRepository
        );
    }

}
