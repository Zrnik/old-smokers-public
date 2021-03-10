<?php


namespace App\Model\Security;


use App\Model\Discord\Connection\Guilds;
use App\Model\Discord\Connection\Messages;
use App\Model\Discord\Connection\OAuth2;
use GuzzleHttp\Exception\GuzzleException;
use Nette\Security\Authenticator;
use Nette\Utils\JsonException;
use Throwable;

class DiscordAuthenticator implements Authenticator
{

    public function __construct(
        private Guilds $guilds, private OAuth2 $OAuth2,
        private Messages $messages,
        private DiscordUserRepository $discordUserRepository
    )
    {
    }

    /**
     * @param string $user
     * @param string $password
     * @return DiscordIdentity
     * @throws GuzzleException
     * @throws JsonException
     * @throws Throwable
     */
    function authenticate(string $user, string $password = ''): DiscordIdentity
    {
        unset($password); // We dont care about the password...

        $discordUser = $this->OAuth2->getMe($user);
        $guildMember = $this->guilds->getMember($discordUser->getId());

        // bdump($discordUser,"Authenticator's GetMe!");

        $isNew = $this->discordUserRepository->updateUser($discordUser);

        if ($isNew) {

            $userLink = $this->discordUserRepository->getUserLink($discordUser->getId());

            $this->messages->sendNotification(
                sprintf(
                    "Na našem webu se registroval nový uživatel %s!",
                    $userLink
                )
            );
        }

        $roles = [];
        if ($guildMember !== null)
            $roles = $guildMember->getRoles();

        return new DiscordIdentity(
            $discordUser, $roles, $user
        );

    }
}
