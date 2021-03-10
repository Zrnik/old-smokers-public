<?php


namespace App\Model\Applications;


use Brick\DateTime\LocalDateTime;
use Michelf\Markdown;

class ApplicationComment
{
    private final function __construct(){}

    public ?int $id = null;

    public int $application;
    public int $discordId;
    public LocalDateTime $time;
    public string $text;

    public function getMarkdown(): string
    {
        return "<span class='markdown'>".Markdown::defaultTransform(strip_tags($this->text))."</span>";
    }

    /**
     * @param array<mixed> $ary
     * @return ApplicationComment
     */
    public static function fromArray(array $ary): ApplicationComment
    {
        $ac = new ApplicationComment();

        $ac->id = $ary["id"];
        $ac->application = $ary["application"];
        $ac->discordId = $ary["discordId"];
        $ac->time = LocalDateTime::parse($ary["commentTime"]);
        $ac->text = $ary["commentText"];

        return $ac;
    }

}
