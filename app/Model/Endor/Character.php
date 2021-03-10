<?php


namespace App\Model\Endor;


use JetBrains\PhpStorm\ArrayShape;

class Character
{

    public ?int $id = null;

    public int $ownerId = 0;

    public string $name = '';

    public int $race = Races::Human;
    public int $job = Jobs::Wanderer;

    public int $boughtLevel = 0;
    public int $exps = 10000;

    public int $notoriety = Notoriety::Blue;

    public function getTag(): string
    {
        if($this->notoriety === Notoriety::PlayerKiller)
            return '[MURDERER]';

        if($this->notoriety === Notoriety::PvPLeague)
        {
            if($this->boughtLevel > 30)
                return "[Master]";

            if($this->boughtLevel > 20)
                return "[Veteran]";

            return '[Apprentice]';
        }

        return '';
    }

    public function getRealLevel(): int
    {
        // Lower exps means automatically level 0...
        if ($this->exps < 10000)
            return 0;

        // Loop:
        $killSwitch = 0;

        $currentCheckExp = 10000;
        $currentCheckLevel = 0;

        while($killSwitch++ < 10000) // While true s killswitchem kdyby se neco pokazilo.
        {
            $expsToNextLevel = ($currentCheckExp * 1.1);

            if($this->exps > $expsToNextLevel)
            {
                $currentCheckExp = $expsToNextLevel;
                $currentCheckLevel++;
            }
            else
            {
                break;
            }
        }

        return $currentCheckLevel;
    }

    public function notoClass(): string
    {
        if ($this->notoriety === Notoriety::Blue)
            return "text-blue-500";

        if ($this->notoriety === Notoriety::PvPLeague)
            return "text-orange-700";

        if ($this->notoriety === Notoriety::PlayerKiller)
            return "text-red-700";

        return '';
    }


    private final function __construct()
    {
    }

    public static function new(): Character
    {
        return new Character();
    }

    /**
     * @param array<mixed> $ary
     * @return Character
     */
    public static function fromArray(array $ary): Character
    {
        $character = new Character();

        $character->id = $ary["id"];
        $character->name = $ary["charname"];

        $character->ownerId = $ary["discordId"];

        $character->race = $ary["race"];
        $character->job = $ary["job"];

        $character->boughtLevel = $ary["bought_level"];
        $character->exps = $ary["exps"];

        $character->notoriety = $ary["notoriety"];

        return $character;
    }

    /**
     * @return array<mixed>
     */
    #[ArrayShape(
        [
            "id" => "int|null",
            "discordId" => "int",
            "charname" => "string",
            "race" => "int",
            "job" => "int",
            "bought_level" => "int",
            "exps" => "int",
            "notoriety" => "int"
        ]
    )]
    public function toArray(): array
    {
        return [
            "id" => $this->id,

            "discordId" => $this->ownerId,

            "charname" => $this->name,

            "race" => $this->race,
            "job" => $this->job,

            "bought_level" => $this->boughtLevel,
            "exps" => $this->exps,

            "notoriety" => $this->notoriety
        ];
    }
}
