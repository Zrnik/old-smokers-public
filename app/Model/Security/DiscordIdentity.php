<?php


namespace App\Model\Security;


use App\Model\Discord\Guilds\GuildRole;
use App\Model\Discord\User;
use JetBrains\PhpStorm\Pure;
use Nette\Security\IIdentity;


class DiscordIdentity implements IIdentity
{

    /**
     * DiscordIdentity constructor.
     * @param User $discordUser
     * @param GuildRole[] $roles
     * @param string $accessToken
     */
    public function __construct(
        private User $discordUser,
        private array $roles,
        private string $accessToken
    )
    {
    }

    /**
     * @return int
     */
    #[Pure]
    public function getId(): int
    {
        return $this->discordUser->getId();
    }

    /**
     * @return GuildRole[]
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    public function getData(): User
    {
        return $this->discordUser;
    }

    #[Pure] public function getUsername(): string
    {
        return $this->getData()->getUsername();
    }


}
