<?php


namespace App\Model\ScreenShots;


use Brick\DateTime\LocalDate;
use Brick\DateTime\LocalDateTime;
use JetBrains\PhpStorm\ArrayShape;
use Nette\Database\Row;

class ScreenComment
{
    public ?int $id;
    public ?int $originalId = null;

    public int $screenShotId;

    public ?int $authorId = null;
    public string $legacyAuthor = '';

    public string $text = '';

    public LocalDateTime $date;

    private final function __construct() {}

    /**
     * @param array<mixed> $ary
     * @return ScreenComment
     */
    public static function fromArray(array $ary): ScreenComment
    {
        $screenComment = new ScreenComment();

        $screenComment->id = $ary["id"];

        $screenComment->authorId = $ary["author"];

        $screenComment->screenShotId = $ary["screenshot"];

        $screenComment->legacyAuthor = $ary["author_legacy"];

        $screenComment->originalId = $ary["screenshot"];

        $screenComment->text = $ary["comment_text"];

        $screenComment->date = LocalDateTime::parse($ary["comment_date"]);

        return $screenComment;
    }

    /**
     * @param Row<mixed> $row
     * @return ScreenComment
     */
    public static function fromRow(Row $row): ScreenComment
    {

        return static::fromArray(iterator_to_array($row));
    }

    public static function create(): static
    {
        return new static();
    }

    /**
     * @return array<mixed>
     */
    #[ArrayShape(["screenshot" => "int", "author" => "int", "author_legacy" => "string", "original_id" => "int|null", "comment_date" => "string", "comment_text" => "string"])]
    public function toArray(): array
    {
        return [
            "screenshot" => $this->screenShotId,

            "author" => $this->authorId,

            "author_legacy" => $this->legacyAuthor,
            "original_id" => $this->originalId,

            "comment_date" => $this->date->jsonSerialize(),

            "comment_text" => $this->text,
        ];
    }
}
