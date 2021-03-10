<?php

namespace App\Presenters;

use App\Model\Applications\Application;
use App\Model\Applications\ApplicationComment;
use App\Model\Discord\Connection\Guilds;
use App\Model\Discord\User;
use App\Model\Endor\Character;
use App\Model\Security\DiscordUserRepository;

class ApplicationTemplate extends BaseTemplate
{

    /**
     * @var Application[]
     */
    public array $applicationList;

    public Application $application;

    /**
     * @var Character[]
     */
    public array $characters;

    public User $applicationOwner;

    public DiscordUserRepository $discordUserRepository;

    /**
     * @var ApplicationComment[]
     */
    public array $comments;

    public Guilds $guilds;

}
