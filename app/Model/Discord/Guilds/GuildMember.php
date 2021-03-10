<?php


namespace App\Model\Discord\Guilds;


use App\Model\Discord\User;
use JetBrains\PhpStorm\Pure;
use Throwable;

class GuildMember
{
    /**
     * GuildMember constructor.
     * @param array<mixed> $data
     * @param Guild $guild
     */
    public function __construct(
        private array $data, private Guild $guild
    )
    {
    }

    #[Pure]
    public function getId(): int
    {
        return $this->getUser()->getId();
    }

    #[Pure]
    public function getUser(): User
    {
        return new User($this->data["user"]);
    }

    public function getTopRole(): ?GuildRole
    {
        $topRole = null;

        foreach ($this->getRoles() as $role) {
            $topRole = $role;
        }

        return $topRole;
    }

    public function getHexColor(): string
    {
        $topRole = $this->getTopRole();
        if($topRole !== null)
            return $topRole->getHexColor();
        return  "#ffffff";
    }

    /**
     * Roles sorted by position!
     *
     * @return array<GuildRole>
     */
    public function getRoles(): array
    {
        $roles = [];

        foreach ($this->guild->getRoles() as $guildRole)
            if (in_array($guildRole->getId(), $this->data["roles"]))
                $roles[$guildRole->getPosition()] = $guildRole;

        ksort($roles);

        return $roles;
    }

    #[Pure]
    public function getNickname(): string
    {
        if ($this->data["nick"] !== null)
            return strval($this->data["nick"]);

        return $this->getUser()->getUsername();
    }

    #[Pure]
    public function getDiscriminator(): string
    {
        return $this->getUser()->getDiscriminator();
    }

    public function hasRole(GuildRole $requiredRole): bool
    {
        foreach ($this->getRoles() as $myRole)
            if ($requiredRole->getId() === $myRole->getId())
                return true;

        return false;
    }

    /**
     * Hlasovat mohou clenove krome adeptu.
     * @return bool
     * @throws Throwable
     */
    public function canVote(): bool
    {
        return $this->isMember(false);
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
        $memberRoles = [
            "Guild Master",
            "Rada Guildy",
            "Člen Guildy"
        ];

        if ($includeAdepts)
            $memberRoles[] = "Adept";

        foreach ($this->getRoles() as $ownedRole)
            if (in_array($ownedRole->getName(), $memberRoles))
                return true;

        return false;
    }


    /**
     * Je to Guild Master nebo člen rady?
     */
    public function isDeputy(): bool
    {
        $deputyRoles = [
            "Guild Master",
            "Rada Guildy"
        ];

        foreach ($this->getRoles() as $ownedRole)
            if (in_array($ownedRole->getName(), $deputyRoles))
                return true;

        return false;
    }




}
