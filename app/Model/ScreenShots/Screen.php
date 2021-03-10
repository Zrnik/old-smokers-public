<?php


namespace App\Model\ScreenShots;


use Brick\DateTime\LocalDateTime;
use Exception;
use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\Pure;
use Nette\Database\Row;
use Nette\Utils\Image;

class Screen
{
    public ?int $id = null;

    public ?int $authorId = null;
    public ?int $originalId = null; // ze screens.endor.cz

    public LocalDateTime $date;

    public string $legacyUploader = '';

    public string $title;

    /**
     * @var ScreenComment[]
     */
    public array $comments;


    private final function __construct()
    {
        $this->comments = [];
    }


    #[Pure]
    public static function create(): Screen
    {
        return new Screen();
    }



    /**
     * @return array<mixed>
     */
    #[ArrayShape(["id" => "int|null", "uploader" => "int|null", "uploader_legacy" => "string", "original_id" => "int|null", "title" => "string", "image_date" => "string"])]
    public function toArray(): array
    {
        return [
            "id" => $this->id,

            "uploader" => $this->authorId,

            "uploader_legacy" => $this->legacyUploader,
            "original_id" => $this->originalId,

            "title" => $this->title,

            "image_date" => $this->date->jsonSerialize(),
        ];
    }

    /**
     * @param Row<mixed> $imageRow
     * @param array<ScreenComment> $Comments
     * @return static
     */
    public static function fromRowWithComments(Row $imageRow, array $Comments): static
    {
        return self::fromArrayWithComments(iterator_to_array($imageRow), $Comments);
    }

    /**
     * @param array<mixed> $imageArray
     * @param array<ScreenComment> $Comments
     * @return static
     */
    public static function fromArrayWithComments(array $imageArray, array $Comments): static
    {
        $newScreen = new static();


        $newScreen->id = intval($imageArray["id"]);

        $newScreen->authorId = $imageArray["uploader"];

        $newScreen->legacyUploader = $imageArray["uploader_legacy"];
        $newScreen->originalId = $imageArray["original_id"];

        $newScreen->title = $imageArray["title"];

        $newScreen->date = LocalDateTime::parse($imageArray["image_date"]);

        $newScreen->comments = $Comments;

        return $newScreen;
    }

}
