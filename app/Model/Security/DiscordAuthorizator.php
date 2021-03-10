<?php


namespace App\Model\Security;


use App\Model\Discord\Connection\Guilds;
use App\Model\Discord\Guilds\GuildRole;
use Nette\InvalidStateException;
use Nette\Security\Authorizator;
use Nette\Security\Permission;

class DiscordAuthorizator implements Authorizator
{
    public function __construct(
        private Guilds $guildConnection
    )
    {
    }


    function isAllowed($role, $resource, $privilege): bool
    {
        if(is_string($role))
            $role = $this->convertDefaultRole($role);

        if (!($role instanceof GuildRole))
            throw new InvalidStateException(
                sprintf(
                    "All roles must be an instance of '%s', got '%s'.",
                    GuildRole::class, get_debug_type($role)
                )
            );


        return $this->getAcl()->isAllowed($role->getName(), $resource, $privilege);
    }

    private function getAcl(): Permission
    {
        $acl = new Permission();

        //Default roles:

        $acl->addRole('guest');
        $acl->addRole('authenticated');

        //Fetch roles from remote:
        foreach($this->guildConnection->getGuild()->getRoles() as $role)
        {
            $acl->addRole($role->getName());
        }


        //Resources:
        $acl->addResource("something");

        return $acl;
    }

    private function convertDefaultRole(string $role): string|GuildRole
    {
        if($role === "guest")
            return new GuildRole([
                "name" => $role
            ]);

        if($role === "authenticated")
            return new GuildRole([
                "name" => $role
            ]);

        return $role;
    }
}
