<?php


namespace App\Model\Security;


use App\Model\Discord\Connection\Guilds;
use App\Model\Discord\User;
use Nette\Database\Connection;
use Throwable;
use Zrnik\MkSQL\Updater;
use Zrnik\MkSQL\Utilities\Installable;

class DiscordUserRepository extends Installable
{

    public function __construct(
        private Connection $connection,
        private Guilds $guilds
    )
    {
        parent::__construct($connection->getPdo());
    }

    function install(Updater $updater): void
    {
        $discordUsersTable = $updater->tableCreate("discord_users")->setPrimaryKeyName("discordId")->setPrimaryKeyType("bigint");
        $discordUsersTable->columnCreate("discordUsername", "varchar(255)");
        $discordUsersTable->columnCreate("discordDiscriminator", "varchar(4)");
        $discordUsersTable->columnCreate("discordLocale", "varchar(2)");
        $discordUsersTable->columnCreate("discordAvatar", "varchar(64)"); // Je to 32, ale rezerva...

        $discordUsersTable->columnCreate("isBanned", "tinyint(1)")->setNotNull()->setDefault(0);
    }

    /**
     * @param int $discordId
     * @return string
     * @throws Throwable
     */
    public function getUserLink(int $discordId): string
    {
        $guildMember = $this->guilds->getMember($discordId);
        if($guildMember !== null)
            return "<@".$discordId.">";

        $member = $this->getUser($discordId);
        if($member !== null)
            return $member->getUsername();

        return 'Neznámý uživatel';
    }

    public function getUser(int $discordId): ?User
    {
        $userRow = $this->connection
            ->fetch(
                "SELECT * FROM discord_users WHERE discordId = ?", $discordId
            );

        if($userRow !== null)
        {
            return User::fromArray(iterator_to_array($userRow));
        }

        return null;
    }

    /**
     * Returns if the user is new or not.
     *
     * @param User $discordUser
     * @return bool
     */
    public function updateUser(User $discordUser): bool
    {
        $user = $this->connection
            ->fetch(
                "SELECT * FROM discord_users WHERE discordId = ?",
                $discordUser->getId()
            );

        if ($user === null) {

            // Vytvoříme uživatele:
            $this->connection->query("INSERT INTO discord_users", [
                "discordId" => $discordUser->getId(),
                "discordUsername" => $discordUser->getUsername(),
                "discordDiscriminator" => $discordUser->getDiscriminator(),
                "discordLocale" => $discordUser->getLocale(),
                "discordAvatar" => $discordUser->getAvatar(),
            ]);
            return true;

        } else {

            $this->connection->query(
                "UPDATE discord_users SET",
                [
                    "discordUsername" => $discordUser->getUsername(),
                    "discordDiscriminator" => $discordUser->getDiscriminator(),
                    "discordLocale" => $discordUser->getLocale(),
                    "discordAvatar" => $discordUser->getAvatar(),
                ],
                "WHERE discordId = ?",
                $discordUser->getId()
            );
            return false;

        }

    }

}
