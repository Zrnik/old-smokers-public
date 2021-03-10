<?php


namespace App\Presenters;


use App\Model\Endor\Character;
use App\Model\Endor\CharacterRepository;
use Nette\DI\Attributes\Inject;

class ProfileTemplate extends BaseTemplate
{
    /**
     * @var Character[]
     */
    public ?array $characters = null;

    /**
     * @var ?Character
     */
    public ?Character $deletedCharacter = null;

    public ?int $characterId;


}
