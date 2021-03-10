<?php


namespace App\Model\Applications;


use Brick\DateTime\LocalDateTime;
use Brick\DateTime\TimeZone;
use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\Pure;
use Michelf\Markdown;
use Nette\Utils\Json;
use Nette\Utils\JsonException;

class Application
{
    public ?int $id = null;
    public int $ownerId = 0;

    public string $text = "";
    public LocalDateTime $lastAction;

    public bool $notifications = false;


    /**
     * @var MemberVote[]
     */
    public array $memberVotes = [];
    public bool $votingStarted = false;
    public int $votesRequired;

    private final function __construct()
    {
        $this->lastAction = LocalDateTime::now(TimeZone::parse(date_default_timezone_get()));
    }

    /**
     * @param array<mixed> $ary
     * @return Application
     * @throws JsonException
     */
    public static function fromArray(array $ary): Application
    {
        $application = static::new();

        $application->id = $ary["id"];

        $application->ownerId = $ary["discordId"];

        $application->text = $ary["applicationText"];

        $application->notifications = $ary["notificationsEnabled"];

        $application->lastAction = LocalDateTime::parse($ary["lastAction"]);

        $application->votingStarted = $ary["votingStarted"];
        $application->votesRequired = $ary["votesRequired"];

        $application->memberVotes = $application->decodeVotes($ary["memberVotes"]);

        return $application;

    }


    #[Pure]
    public function isResolved(): bool
    {
        $agree = 0;
        $disagree = 0;

        foreach ($this->memberVotes as $memberVote) {
            if ($memberVote->agreed)
                $agree++;

            if (!$memberVote->agreed)
                $disagree++;
        }

        return $agree >= $this->votesRequired || $disagree >= $this->votesRequired;
    }

    #[Pure]
    public function isAccepted(): bool
    {
        $agreeVotes = 0;
        foreach ($this->memberVotes as $vote)
            if ($vote->agreed)
                $agreeVotes++;

        return $agreeVotes >= $this->votesRequired;
    }

    public function getVoteOf(int $userId): ?MemberVote
    {
        foreach ($this->memberVotes as $vote)
            if ($vote->discordId === $userId)
                return $vote;
        return null;
    }

    /**
     * @param int $userId
     * @param bool $agreed
     */
    public function castVote(int $userId, bool $agreed): void
    {
        $voteStack = [];

        // Vezmeme všehny hlasy krom toho ktery
        // castime (at uz existuje nebo ne)
        foreach ($this->memberVotes as $vote)
            if ($vote->discordId !== $userId)
                $voteStack[] = $vote;

        // Vytvoříme hlas
        $newVote = new MemberVote();
        $newVote->discordId = $userId;
        $newVote->agreed = $agreed;

        // A přidáme k nim náš nový
        $voteStack[] = $newVote;

        // a takhle to uložíme...
        $this->memberVotes = $voteStack;
    }

    public function getMarkdown(): string
    {
        return "<span class='markdown'>".Markdown::defaultTransform(strip_tags($this->text))."</span>";
    }

    public static function new(): Application
    {
        return new Application();
    }

    /**
     * @return array<mixed>
     * @throws JsonException
     */
    #[ArrayShape(
        [
            "id" => "int|null",
            "discordId" => "int",
            "applicationText" => "string",
            "notificationsEnabled" => "bool",
            "lastAction" => "string",
            "votingStarted" => "bool",
            "votesRequired" => "int",
            "memberVotes" => "string"
        ]
    )]
    public function toArray(): array
    {
        return [
            "id" => $this->id,
            "discordId" => $this->ownerId,
            "applicationText" => $this->text,

            "notificationsEnabled" => $this->notifications,

            "lastAction" => $this->lastAction->jsonSerialize(),
            "votingStarted" => $this->votingStarted,
            "votesRequired" => $this->votesRequired,

            "memberVotes" => $this->encodeVotes()
        ];
    }

    /**
     * @return string
     * @throws JsonException
     */
    private function encodeVotes(): string
    {
        $toEncode = [];

        foreach ($this->memberVotes as $vote)
            $toEncode[] = $vote->toArray();

        return Json::encode($toEncode);
    }

    /**
     * @param string $votesJson
     * @return MemberVote[]
     * @throws JsonException
     */
    protected function decodeVotes(string $votesJson): array
    {
        $result = [];
        $decoded = Json::decode($votesJson, Json::FORCE_ARRAY);
        foreach ($decoded as $votedata)
            $result[] = MemberVote::fromArray($votedata);

        return $result;

    }

    public function getGreetingsText(): string
    {
        return "Zdravíčko,\n\njá jsem `bot` guildy **Old Smokers** a rád " .
            "vás poznávám, budu vás zde informovat kdyby vám někdo odepsal na vaši přihlášku i s odkazem, " .
            "aby jste nemuseli stále kontrolovat stránku.\n\nHezký den a mnoho zdaru!";
    }

}
