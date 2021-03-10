<?php


namespace App\Model\Discord\Connection;


use App\Model\Discord\Guilds\Guild;
use App\Model\Discord\Guilds\GuildMember;
use GuzzleHttp\Client;
use Nette\Caching\Cache;
use Nette\Caching\Storage;
use Nette\Utils\Json;
use Throwable;

class Guilds
{


    private Cache $cache;

    private Client $httpClient;

    public function __construct(Storage $storage)
    {
        $this->cache = new Cache($storage, static::class);
        $this->httpClient = new Client();
    }

    private int $guildId = -1;
    private string $botToken = '';

    public function setGuildId(int $guildId): void
    {
        $this->guildId = $guildId;
    }

    public function setBotToken(string $botToken): void
    {
        $this->botToken = $botToken;
    }

    public function clearCache(): void
    {
        $this->cache->clean([
            Cache::ALL => true,
        ]);
    }

    /**
     * @return Guild
     * @throws Throwable
     */
    public function getGuild(): Guild
    {
        return new Guild(
            $this->cache->load(
                "guild" . $this->guildId,
                function (&$dependencies): array {
                    $dependencies[Cache::EXPIRE] = '10 minutes';

                    return Json::decode(
                        $this->httpClient->get(
                            "https://discord.com/api/guilds/" . $this->guildId,
                            [
                                "headers" => [
                                    "Authorization" => "Bot " . $this->botToken
                                ],
                                "form_data" => [
                                    "with_counts" => true
                                ]
                            ]
                        )->getBody()->__toString(),
                        Json::FORCE_ARRAY
                    );
                }
            )
        );
    }

    /**
     * @return GuildMember[]
     * @throws Throwable
     */
    public function getGuildMembers(): array
    {
        $guild = $this->getGuild();

        return $this->cache->load(
            "guildMembers" . $this->guildId,
            function (&$dependencies) use ($guild): array {
                $dependencies[Cache::EXPIRE] = '10 minutes';

                $result = [];

                $memberList = Json::decode(
                    $this->httpClient->get(
                        "https://discord.com/api/guilds/" . $this->guildId . "/members?limit=1000",
                        [
                            "headers" => [
                                "Authorization" => "Bot " . $this->botToken
                            ]
                        ]
                    )->getBody()->__toString(),
                    Json::FORCE_ARRAY
                );

                foreach($memberList as $member)
                {
                    $result[] = new GuildMember(
                        $member, $guild
                    );
                }

                return $result;

            }

        );

    }

    /**
     * @param int $id
     * @return GuildMember|null
     * @throws Throwable
     */
    public function getMember(int $id): ?GuildMember
    {
        $list = $this->getGuildMembers();

        foreach($list as $guildMember)
            if($guildMember->getUser()->getId() === $id)
                return $guildMember;

        return null;
    }

}
