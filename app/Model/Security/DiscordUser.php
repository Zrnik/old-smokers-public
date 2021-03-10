<?php


namespace App\Model\Security;

use App\Model\Applications\Application;
use App\Model\Applications\ApplicationRepository;
use App\Model\Discord\Connection\Guilds;
use App\Model\Discord\Guilds\GuildMember;
use App\Model\Endor\Character;
use App\Model\Endor\CharacterRepository;
use JetBrains\PhpStorm\Pure;
use Nette\InvalidStateException;
use Nette\Security\Authorizator;
use Nette\Security\IAuthenticator;
use Nette\Security\IUserStorage;
use Nette\Security\User;
use Nette\Security\UserStorage;
use Nette\Utils\Strings;
use Throwable;

/**
 * Class User
 * @package App\Model\Authentication
 * @property DiscordIdentity $identity
 */
class DiscordUser extends User
{

    public function __construct(

        private Guilds $guilds, private ApplicationRepository $applicationRepository,
        private CharacterRepository $characterRepository, private DiscordUserRepository $discordUserRepository,
        IUserStorage $legacyStorage = null, IAuthenticator $authenticator = null,
        Authorizator $authorizator = null, UserStorage $storage = null
    )
    {
        parent::__construct($legacyStorage, $authenticator, $authorizator, $storage);
    }


    /**
     * @return GuildMember|null
     * @throws Throwable
     */
    public function getGuildMember(): ?GuildMember
    {
        return $this->guilds->getMember($this->getId());
    }


    /**
     * Má-li uživatel jednu roli která je členská, je členem.
     *
     * @param bool $includeAdepts
     * @return bool
     * @throws Throwable
     */
    public function isMember(bool $includeAdepts = true): bool
    {
        return $this->getGuildMember()?->isMember($includeAdepts) ?? false;
    }

    /**
     * Je to Guild Master nebo člen rady?
     * @throws Throwable
     */
    public function isDeputy(): bool
    {
        return $this->getGuildMember()?->isDeputy() ?? false;
    }

    /**
     * @return bool
     * @throws Throwable
     */
    public function canVote(): bool
    {
        return $this->getGuildMember()?->canVote() ?? false;
    }

    public function hasApplication(): bool
    {
        return $this->getApplication() !== null;
    }

    public function getApplication(): ?Application
    {
        return $this->applicationRepository->getApplicationOf($this->getId());
    }

    /**
     * @return Character[]
     */
    private function getCharacters(): array
    {
        return $this->characterRepository->getCharactersOf($this->getId());
    }

    public function hasCharacter(): bool
    {
        return count($this->getCharacters()) > 0;
    }

    #[Pure]
    public function getUsername(): string
    {
        return $this->identity->getUsername();
    }

    public function getUser(): \App\Model\Discord\User
    {
        $user = $this->discordUserRepository->getUser($this->getId());

        if ($user === null)
            throw new InvalidStateException("The user should exist!");

        return $user;
    }

    public function hasRole(string $roleName): bool
    {
        if($roleName === "guest")
            return true;

        if($this->isLoggedIn() && $roleName === "authenticated")
            return true;

        if($this->identity === null)
            return false;

        foreach ($this->identity->getRoles() as $guildRole)
            if (Strings::webalize($guildRole->getName()) === Strings::webalize($roleName))
                return true;

        return false;
    }


}
