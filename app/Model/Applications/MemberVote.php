<?php


namespace App\Model\Applications;


use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\Pure;

class MemberVote
{
    public int $discordId = 0;
    public bool $agreed = false;

    /**
     * @return array<mixed>
     */
    #[ArrayShape(["discordId" => "int", "agreed" => "bool"])]
    public function toArray(): array
    {
        return [
            "discordId" => $this->discordId,
            "agreed" => $this->agreed
        ];
    }

    /**
     * @param array<mixed> $array
     * @return MemberVote
     */
    #[Pure]
    public static function fromArray(array $array): MemberVote
    {
        $mv = new MemberVote();

        $mv->discordId = $array["discordId"];
        $mv->agreed = $array["agreed"];

        return $mv;
    }
}

