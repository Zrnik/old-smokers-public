<?php


namespace App\Controls\Endor\Character;

use App\Misc\FormRenderer;
use App\Model\Endor\Character;
use App\Model\Endor\CharacterRepository;
use App\Model\Endor\Jobs;
use App\Model\Endor\Notoriety;
use App\Model\Endor\Races;
use App\Model\Security\DiscordUser;
use Closure;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\SelectBox;
use Nette\Forms\Controls\TextInput;

class Editor extends Form
{

    private TextInput $characterName;
    private SelectBox $characterJob;
    private SelectBox $characterRace;

    private TextInput $characterLevel;

    private TextInput $characterExps;
    private SelectBox $characterNotoriety;

    private ?Character $character;

    public function __construct(
        private ?int $characterId,
        private Closure $afterCreate,
        private CharacterRepository $characterRepository,
        private DiscordUser $discordUser
    )
    {
        $this->character = $characterRepository->getCharacter($characterId);

        $this->configure();
        $this->setRenderer(new FormRenderer());
        parent::__construct();
    }

    private function configure(): void
    {

        $this->characterName = $this->addText("character_name", "Jméno postavy");
        $this->characterName->setRequired();

        if($this->character !== null)
            $this->characterName->setDefaultValue($this->character->name);

        $this->characterJob = $this->addSelect(
            "character_job",
            "Povolání",
            array_flip(Jobs::toArray())
        )->setRequired();


        if($this->character !== null)
            $this->characterJob->setDefaultValue($this->character->job);


        $this->characterRace = $this->addSelect("character_race", "Rasa",
            array_flip(Races::toArray())
        )->setRequired();

        if($this->character !== null)
            $this->characterRace->setDefaultValue($this->character->race);

        $this->characterLevel = $this->addInteger("character_level", "Level")->setRequired();

        if($this->character !== null)
            $this->characterLevel->setDefaultValue($this->character->boughtLevel);

        $this->characterExps = $this->addInteger("character_exps", "Expy postavy")
            ->setOption("description", "Pro výpočet reálného levelu.")->setRequired();

        if($this->character !== null)
            $this->characterExps->setDefaultValue($this->character->exps);

        $characterNoto = [
            Notoriety::Blue => "Modrák",
            Notoriety::PvPLeague => "Ligař",
            Notoriety::PlayerKiller => "Vrah (PK)",
        ];

        $this->characterNotoriety = $this->addSelect("character_noto", "Stav postavy",
            $characterNoto
        )->setRequired();


        if($this->character !== null)
            $this->characterNotoriety->setDefaultValue($this->character->notoriety);


        $this->addSubmit("submit", $this->characterId === null ? "Vytvořit postavu" : "Uložit změny")
            ->controlPrototype->class("btn btn-sm text-green-700 bg-green-300");

        $this->onSuccess[] = [$this, 'saveData'];
    }

    public function saveData(): void
    {
        $character = $this->character ?? Character::new();

        $character->ownerId = $character->ownerId === 0 ? intval($this->discordUser->getId()) : $character->ownerId;

        $character->name = strval($this->characterName->getValue());

        $character->job = intval($this->characterJob->getValue());
        $character->race = intval($this->characterRace->getValue());

        $character->boughtLevel = intval($this->characterLevel->getValue());
        $character->exps = intval($this->characterExps->getValue());

        $character->notoriety = intval($this->characterNotoriety->getValue());

        $this->characterRepository->save($character);

        if (!$this->hasErrors())
            $this->afterCreate->call($this->getPresenter()??$this);
    }

}
