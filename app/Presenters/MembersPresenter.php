<?php


namespace App\Presenters;

use App\Model\Discord\Connection\Guilds;
use App\Model\Discord\Guilds\GuildRole;
use App\Model\Endor\CharacterRepository;
use Nette\DI\Attributes\Inject;
use Throwable;

/**
 * Class MembersPresenter
 * @package App\Presenters
 * @property MembersTemplate $template
 */
class MembersPresenter extends BasePresenter
{

    #[Inject]
    public Guilds $guildConnection;

    #[Inject]
    public CharacterRepository $characterRepository;

    /**
     * @throws Throwable
     */
    public function renderDefault(): void
    {

        // Create a member list:

        $guildInfo = $this->guildConnection->getGuild();

        $memberList = [
            // Guild Master
            [
                "role" => $guildInfo->getRole("Guild Master"),
                "members" => [],
            ],

            // Rada
            [
                "role" => $guildInfo->getRole("Rada Guildy"),
                "members" => []
            ],

            // Čleonové
            [
                "role" => $guildInfo->getRole("Člen Guildy"),
                "members" => []
            ],

            // Adepti na přijetí

            //TODO: Adepty budeme hrotit až budeme
            // striktně vyžadovat přihlášky, teď to je jedno

            /*[
                "role" => $guildInfo->getRole("Adept"),
                "members" => []
            ],*/
        ];

        $remoteGuildMembers = $this->guildConnection->getGuildMembers();

        $seenUsers = [];

        foreach ($memberList as $memberListKey => $data) {

            /**
             * @var GuildRole $requiredRole
             */
            $requiredRole = $data["role"];

            foreach ($remoteGuildMembers as $guildMember) {
                if ($guildMember->hasRole($requiredRole)) {

                    if (in_array($guildMember->getId(), $seenUsers))
                        continue;

                    $seenUsers[] = $guildMember->getId();

                    $memberList[$memberListKey]["members"][] = $guildMember;

                }
            }
        }

        $this->template->memberList = $memberList;

        $this->template->characters = $this->characterRepository->getCharactersOfMultiple($seenUsers);
    }

}
