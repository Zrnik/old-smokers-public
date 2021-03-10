<?php


namespace App\Model\Discord\Guilds;


use JetBrains\PhpStorm\Pure;

class GuildRole
{
    /**
     * GuildRole constructor.
     * @param array<mixed> $data
     */
    public function __construct(
        public array $data
    )
    {
    }

    public function getName(): string
    {
        return $this->data["name"];
    }

    #[Pure]
    public function getHexColor(): string
    {
        return "#".dechex(intval($this->data["color"]));
    }

    #[Pure]
    public function getId(): int
    {
        return intval($this->data["id"]);
    }

    #[Pure]
    public function getPosition(): int
    {
        return intval($this->data["position"]);
    }
}
