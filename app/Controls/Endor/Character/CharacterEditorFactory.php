<?php


namespace App\Controls\Endor\Character;


use App\Model\Endor\CharacterRepository;
use App\Model\Security\DiscordUser;
use Closure;

class CharacterEditorFactory
{

    public function __construct(
        private CharacterRepository $characterRepository,
        private DiscordUser $discordUser
    )
    {
    }

    public function create(?int $characterId, Closure $afterCreate): Editor
    {
        return new Editor($characterId, $afterCreate, $this->characterRepository, $this->discordUser);
    }
}
