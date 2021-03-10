<?php


namespace App\Model\Discord;


use App\Model\Discord\Connection\Guilds;
use JetBrains\PhpStorm\Pure;
use Nette\Http\UrlScript;

class User
{
    /**
     * User constructor.
     * @param array<mixed> $data
     */
    public function __construct(private array $data)
    {
    }

    /**
     * @param array<mixed> $data
     * @return User
     */
    public static function fromArray(array $data): User
    {
        return new User([
            "id" => $data["discordId"],
            "username" => $data["discordUsername"],
            "discriminator" => $data["discordDiscriminator"],
            "locale" => $data["discordLocale"],
            "avatar" => $data["discordAvatar"],
        ]);
    }



    #[Pure]
    public function getId(): int
    {
        return intval($this->data["id"]);
    }

    #[Pure]
    public function getUsername(): string
    {
        return strval($this->data["username"]);
    }

    #[Pure]
    public function getDiscriminator(): string
    {
        return strval($this->data["discriminator"]);
    }

    #[Pure]
    public function getEmail(): ?string
    {
        return $this->data["email"];
    }

    public function getAvatarUrl(int $size = 128): UrlScript
    {
        if ($this->data["avatar"] === null)
            return self::getDefaultAvatarUrl(intval($this->getDiscriminator()));

        $url = new UrlScript("https://cdn.discordapp.com/");

        $url = $url->withPath(
            "avatars/" . $this->data["id"] . "/" . $this->getAvatar() . ".png"
        );

        $url = $url->withQuery(["size" => $size]);

        return $url;
    }

    public static function getDefaultAvatarUrl(int $discriminator): UrlScript
    {
        $url = new UrlScript("https://discordapp.com/");

        $mod5 = [
            0 => "6debd47ed13483642cf09e832ed0bc1b",
            1 => "322c936a8c8be1b803cd94861bdfa868",
            2 => "dd4dbc0016779df1378e7812eabaa04d",
            3 => "0e291f67c9274a1abdddeb3fd919cbaa",
            4 => "1cbd08c76f8af6dddce02c5138971129"
        ];

        $url = $url->withPath(
            "assets/" . $mod5[$discriminator % 5] . ".png"
        );

        return $url;
    }

    public function getAvatar(): ?string
    {
        return $this->data["avatar"];
    }

    public function getLocale(): string
    {
        return $this->data["locale"];
    }

}
