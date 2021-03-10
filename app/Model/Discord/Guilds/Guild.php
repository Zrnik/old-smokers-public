<?php


namespace App\Model\Discord\Guilds;


class Guild
{
    /**
     * Guild constructor.
     * @param array<mixed> $data
     */
    public function __construct(private array $data)
    {
    }

    /**
     * @return GuildRole[]
     */
    public function getRoles(): array
    {
        $roles = [];

        foreach($this->data["roles"] as $role)
            $roles[] = new GuildRole($role);

        return $roles;
    }

    public function getRole(string $name): ?GuildRole
    {
        foreach($this->getRoles() as $role)
            if($role->getName() === $name)
                return $role;

        return null;
    }

}

