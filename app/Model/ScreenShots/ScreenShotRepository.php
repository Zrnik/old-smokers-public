<?php

namespace App\Model\ScreenShots;

use App\Model\Discord\Connection\Guilds;
use App\Model\Discord\User;
use App\Model\Security\DiscordUser;
use App\Model\Security\DiscordUserRepository;
use Brick\DateTime\LocalDateTime;
use Brick\DateTime\TimeZone;
use Exception;
use Nette\Database\Connection;
use Nette\Database\Explorer;
use Nette\Database\Row;
use Nette\Database\Table\Selection;
use Nette\DI\Container;
use Nette\Http\FileUpload;
use Nette\Utils\Image;
use Nette\Utils\Paginator;
use Nette\Utils\Random;
use Throwable;
use Ublaboo\DataGrid\Filter\Filter;
use Zrnik\MkSQL\Updater;
use Zrnik\MkSQL\Utilities\Installable;

class ScreenShotRepository extends Installable
{
    const ThumbnailX = 230;
    const ThumbnailY = 230;

    const ImageQuality = 80;
    const ThumbnailQuality = 60;

    private string $screenShotImagePath;


    public function __construct(
        private Connection $connection, private Explorer $explorer,
        private Container $container,
        private DiscordUserRepository $discordUserRepository,
        private Guilds $guilds
    )
    {
        $this->screenShotImagePath =
            $container->parameters["wwwDir"] . "/screenshot-images/";

        if (!file_exists($this->screenShotImagePath))
            @mkdir($this->screenShotImagePath, 0777, true);

        parent::__construct($connection->getPdo());
    }

    function install(Updater $updater): void
    {
        $images = $updater->tableCreate("screenshot_images");

        $images->columnCreate("uploader", "bigint")
            ->addForeignKey("discord_users.discordId", false);

        $images->columnCreate("uploader_legacy", "varchar(255)");
        $images->columnCreate("original_id");

        $images->columnCreate("title", "varchar(255)");

        $images->columnCreate("image_date", "varchar(255)");


        $images->columnCreate("views")->setDefault(0)->setNotNull();

        //$images->columnCreate("base64_image", "longtext");
        //$images->columnCreate("mime_type", "varchar(250)");

        $comments = $updater->tableCreate("screenshot_comment");

        $comments->columnCreate("screenshot")
            ->addForeignKey("screenshot_images.id", false);

        $comments->columnCreate("author", "bigint")
            ->addForeignKey("discord_users.discordId", false);

        $comments->columnCreate("author_legacy", "varchar(255)");
        $comments->columnCreate("original_id");

        $comments->columnCreate("comment_date", "varchar(255)");

        $comments->columnCreate("comment_text", "longtext");

    }

    /**
     * @param DiscordUser $user
     * @param string $title
     * @param FileUpload $fileUpload
     * @return int
     */
    public function create(DiscordUser $user, string $title, FileUpload $fileUpload): int
    {

        // Step 1.: Přidat do databáze!
        $screen = Screen::create();
        $screen->authorId = $user->getId();
        $screen->title = $title;
        $screen->date = LocalDateTime::now(TimeZone::parse(date_default_timezone_get()));

        $this->connection->query("INSERT INTO screenshot_images", $screen->toArray());

        $newId = intval($this->connection->getInsertId());

        // Step 2. Pořešit Obrázek!

        //region Vytvořit temp složku
        $tempDir = $this->container->parameters["tempDir"] . "/screen-transform/";
        if (!file_exists($tempDir))
            @mkdir($tempDir, 0777, true);
        //endregion

        //region Šoupnu do ní obrázek
        $tempFileName = $tempDir . "/" . Random::generate(30);
        $fileUpload->move($tempFileName);
        //endregion

        //region Zpracuju ho na menší velikost a uložím jej jako JPG do složky s obrázky pro screenshoty
        $image = Image::fromFile($tempFileName);
        $image->save($this->pathOriginal($newId, true), ScreenShotRepository::ImageQuality, Image::JPEG);
        //endregion

        //region Vytvořím thumbnail a uložím ho kam potřebuji!
        $image->resize(ScreenShotRepository::ThumbnailX, ScreenShotRepository::ThumbnailY, Image::SHRINK_ONLY);
        $image->save($this->pathThumbnail($newId, true), ScreenShotRepository::ThumbnailQuality, Image::JPEG);
        //endregion

        // Ehm... tadá?

        return $newId;
    }


    public function createComment(DiscordUser $user, int $screenshotId, string $commentText): void
    {
        $comment = ScreenComment::create();
        $comment->authorId = $user->getId();
        $comment->screenShotId = $screenshotId;
        $comment->text = $commentText;
        $comment->date = LocalDateTime::now(TimeZone::parse(date_default_timezone_get()));
        $this->connection->query("INSERT INTO screenshot_comment", $comment->toArray());
    }

    public function count(): int
    {
        $result = $this->connection->fetch("SELECT COUNT(*) as total FROM screenshot_images");

        if ($result !== null)
            return intval($result["total"]);

        return 0;
    }

    public function countAfter(int $id): int
    {
        $who = $this->connection->fetch(
            "SELECT * FROM screenshot_images WHERE id = ?",
            $id
        );

        if($who === null)
            return 0;

        $after = $this->connection->fetchAll(
            "SELECT * FROM screenshot_images WHERE image_date >= ? ORDER BY image_date ASC",
            $who["image_date"]
        );

        $cnt = 0;
        $whoFound = false;
        foreach($after as $row)
        {
            if($row["id"] === $who["id"])
            {
                $whoFound = true;
                continue;
            }

            if($whoFound)
                $cnt++;
        }

        return $cnt;
    }

    public function getPageOf(Screen $image, int $ipp): int
    {
        if ($image->id === null)
            return 1;

        $before = $this->countAfter($image->id);
        $page = 1;
        while ($before >= $ipp) {
            $page++;
            $before -= $ipp;
        }

        return $page;
    }

    /**
     * @param Paginator $paginator
     * @return Screen[]
     */
    public function getImages(Paginator $paginator): array
    {
        $latestImageRows = $this->connection->fetchAll(
            "SELECT * FROM screenshot_images ORDER BY image_date DESC LIMIT ? OFFSET ?",
            $paginator->getLength(), $paginator->getOffset()
        );

        $screenIds = [];
        $commentByImageId = [];
        foreach ($latestImageRows as $imageRow) {
            $commentByImageId[$imageRow["id"]] = [];
            $screenIds[] = $imageRow["id"];
        }

        $commentsOfLatestImages = count($screenIds) > 0 ? $this->connection->fetchAll(
            "SELECT * FROM screenshot_comment WHERE screenshot IN (?)", $screenIds
        ) : [];


        foreach ($commentsOfLatestImages as $commentRow) {
            $commentByImageId[$commentRow["screenshot"]][] = ScreenComment::fromRow($commentRow);
        }


        $data = [];

        foreach ($latestImageRows as $imageRow) {
            $data[] = Screen::fromRowWithComments($imageRow, $commentByImageId[$imageRow["id"]]);
        }

        return $data;
    }

    /**
     * @return array<ScreenComment>
     */
    public function getLatestComments(): array
    {
        $latestComments = $this->connection->fetchAll("SELECT * FROM screenshot_comment ORDER BY comment_date DESC LIMIT 10");

        $data = [];
        foreach ($latestComments as $commentRow) {
            $data[] = ScreenComment::fromRow($commentRow);
        }

        return $data;
    }

    public function getLatestImportId(): int
    {
        $latestComments = $this->connection->fetchAll("SELECT original_id FROM screenshot_images ORDER BY original_id DESC LIMIT 5");

        if (count($latestComments) > 0)
            return intval($latestComments[0]["original_id"]);

        return 0;
    }

    public function wwwDir(): string
    {
        return $this->container->parameters["wwwDir"];
    }

    public function getImage(int $id): ?Screen
    {
        $screenShot = $this->connection->fetch(
            "SELECT * FROM screenshot_images WHERE id = ?", $id
        );

        if ($screenShot === null)
            return null;

        $comments = $this->connection->fetchAll(
            "SELECT * FROM screenshot_comment WHERE screenshot = ?", $id
        );


        $commentObjects = [];

        foreach ($comments as $commentRow)
            $commentObjects[] = ScreenComment::fromRow($commentRow);


        return Screen::fromRowWithComments(
            $screenShot,
            $commentObjects
        );
    }


    public function getNextId(int $id): ?int
    {
        return $this->getNextFutureId($id);
    }


    public function getPreviousId(int $id): ?int
    {
        return $this->getNextPastId($id);
    }

    private function getNextFutureId(int $id): ?int
    {
        $who = $this->connection->fetch(
            "SELECT * FROM screenshot_images WHERE id = ?", $id
        );


        if ($who === null)
            return null;

        // Tady předpokládám (že na starém endoru nikdo nedal 50 screenshotu za den)
        // Na nasem je to jedno, tam je cas vcetne sekund, milisekund atd...
        $candidates = $this->connection->fetchAll(
            "SELECT * FROM screenshot_images WHERE image_date >= ? ORDER BY image_date ASC LIMIT 50",
            $who["image_date"]
        );

        $whoFound = false;
        foreach($candidates as $candidate)
        {
            if($candidate["id"] === $who["id"])
            {
                $whoFound = true;
                continue;
            }

            if($whoFound)
            {
                return intval($candidate["id"]);
            }

        }

        return null;
    }

    private function getNextPastId(int $id): ?int
    {
        $who = $this->connection->fetch(
            "SELECT * FROM screenshot_images WHERE id = ?", $id
        );

        if ($who === null)
            return null;

        // Tady předpokládám (že na starém endoru nikdo nedal 50 screenshotu za den)
        // Na nasem je to jedno, tam je cas vcetne sekund, milisekund atd...
        $candidates = $this->connection->fetchAll(
            "SELECT * FROM screenshot_images WHERE image_date <= ? ORDER BY image_date DESC LIMIT 50",
            $who["image_date"]
        );

        $whoFound = false;
        foreach($candidates as $candidate)
        {
            if($candidate["id"] === $who["id"])
            {
                $whoFound = true;
                continue;
            }

            if($whoFound)
            {
                return intval($candidate["id"]);
            }

        }

        return null;
    }


    /**
     * @param int $id
     * @param int $howManyAround
     * @return array<Row<mixed>>
     * /
     * public function getAround(int $id, int $howManyAround = 5): array
     * {
     * if (array_key_exists($id, $this->aroundOf))
     * return $this->aroundOf[$id];
     *
     * $data = $this->connection->fetchAll(
     * "SELECT id FROM screenshot_images WHERE id > ? ORDER BY id LIMIT ?",
     * $id - $howManyAround, $howManyAround * 2
     * );
     *
     * $this->aroundOf[$id] = $data;
     * return $data;
     * }
     *
     * public function getNextId(int $id): ?int
     * {
     * $data = $this->getAround($id);
     *
     * // Smallest Bigger
     * $result = null;
     *
     * foreach ($data as $row) {
     * $rowId = intval($row["id"]);
     *
     * if ($rowId > $id) {
     * //its bigger!
     *
     * if ($result === null) {
     * // First found, give it a number!
     * $result = $rowId;
     * } else {
     * //Keep the lower
     * $result = min($result, $rowId);
     * }
     * }
     * }
     *
     * return $result;
     *
     * }
     *
     * public function getPreviousId(int $id): ?int
     * {
     * $data = $this->getAround($id);
     *
     * // Biggest Lower
     * $result = null;
     *
     * foreach ($data as $row) {
     * $rowId = intval($row["id"]);
     *
     * if ($rowId < $id) {
     * //its smaller!
     *
     * if ($result === null) {
     * // First found, give it a number!
     * $result = $rowId;
     * } else {
     * //Keep the bigger
     * $result = max($result, $rowId);
     * }
     * }
     * }
     *
     * return $result;
     * }
     * @var array<int, array<Row<mixed>>>
     * /
     * private array $aroundOf = [];
     *
     * /**
     *
     */
    //region Image Handling

    /**
     * @param int $screenshotId
     * @return string
     * @throws Exception
     */
    public function getThumbnailUrl(int $screenshotId): string
    {
        return $this->pathThumbnail($screenshotId, false);
    }

    /**
     * @param int $screenshotId
     * @return string
     * @throws Exception
     */
    public function getImageUrl(int $screenshotId): string
    {
        return $this->pathOriginal($screenshotId, false);
    }

    /**
     * @param int $imageId
     * @param bool $local
     * @return string
     * @internal
     */
    public function pathThumbnail(int $imageId, bool $local = false): string
    {
        $imgName = "image-" . $imageId . ".thumbnail.jpg";

        if ($local)
            return $this->getLocalPath($imgName);

        return $this->getPublicPath($imgName);
    }

    /**
     * @param int $imageId
     * @param bool $local
     * @return string
     * @internal
     */
    public function pathOriginal(int $imageId, bool $local = false): string
    {
        $imgName = "image-" . $imageId . ".jpg";

        if ($local)
            return $this->getLocalPath($imgName);

        return $this->getPublicPath($imgName);
    }

    private function getLocalPath(string $imgName): string
    {
        return $this->screenShotImagePath . "/" . $imgName;
        //return $this->assetLocator->locateInBuildDirectory("/screenshot-images/" . );
    }

    private function getPublicPath(string $imgName): string
    {
        return "/screenshot-images/" . $imgName;
        //return $this->assetLocator->locateInPublicPath("/screenshot-images/" . $imgName);
    }

    /**
     * @param Screen|ScreenComment $screenOrComment
     * @return array<mixed>
     */
    private function getAuthor(Screen|ScreenComment $screenOrComment): array
    {
        $legacyNickname = null;

        if ($screenOrComment instanceof Screen)
            $legacyNickname = $screenOrComment->legacyUploader;

        if ($screenOrComment instanceof ScreenComment)
            $legacyNickname = $screenOrComment->legacyAuthor;

        $realAuthorId = null;

        if ($screenOrComment instanceof Screen)
            $realAuthorId = $screenOrComment->authorId;

        if ($screenOrComment instanceof ScreenComment)
            $realAuthorId = $screenOrComment->authorId;

        return [$legacyNickname, $realAuthorId];
    }


    /**
     * @param Screen|ScreenComment $screenOrComment
     * @return string
     * @throws Throwable
     */
    public function getUploaderName(Screen|ScreenComment $screenOrComment): string
    {
        [$legacyNickname, $realAuthorId] = $this->getAuthor($screenOrComment);

        if ($realAuthorId === null)
            return $legacyNickname ?? 'Unknown Author';

        $guildMember = $this->guilds->getMember($realAuthorId);

        if ($guildMember !== null) {
            return $guildMember->getNickname();
        }

        $uploader = $this->discordUserRepository->getUser($realAuthorId);

        if ($uploader === null)
            return $legacyNickname ?? 'Unknown Author';

        return $uploader->getUsername();
    }

    /**
     * @param Screen|ScreenComment $screenOrComment
     * @return string
     */
    public function getAvatarUrlOfUploader(Screen|ScreenComment $screenOrComment): string
    {
        [$legacyNickname, $realAuthorId] = $this->getAuthor($screenOrComment);

        if ($realAuthorId === null)
            return User::getDefaultAvatarUrl(ord($legacyNickname ?? 'Unknown Author'));

        $uploader = $this->discordUserRepository->getUser($realAuthorId);

        if ($uploader === null)
            return User::getDefaultAvatarUrl(ord($legacyNickname ?? 'Unknown Author'));

        return $uploader->getAvatarUrl();
    }

    //endregion

    /**
     * @param Filter[] $filters
     * @return Selection
     */
    public function getCommentQuery(array $filters): Selection
    {
        $query = $this->explorer->table("screenshot_comment");


        foreach ($filters as $filter) {

            if ($filter->getKey() === "author" && $filter->getValue() !== null) {
                $allowedIds = [];

                // Discord User
                foreach (
                    $this->connection->fetchAll(
                        "SELECT * FROM discord_users WHERE discordUsername LIKE ?",
                        '%' . $filter->getValue() . '%'
                    )
                    as $discordUserRow
                ) {
                    if (!in_array($discordUserRow["discordId"], $allowedIds))
                        $allowedIds[] = $discordUserRow["discordId"];
                };

                // Guild Member
                $guildMembers = $this->guilds->getGuildMembers();
                foreach ($guildMembers as $guildMember) {
                    if (
                        $guildMember->getNickname() !== null &&
                        str_contains(
                            $guildMember->getNickname(),
                            $filter->getValue()
                        )
                    ) {
                        if (!in_array($guildMember->getId(), $allowedIds)) {
                            $allowedIds[] = $guildMember->getId();
                        }
                    }
                }

                $query->where(
                    "author IN (?) OR author_legacy LIKE ?",
                    $allowedIds, "%" . $filter->getValue() . "%"
                );

            }


            if ($filter->getKey() === "text" && $filter->getValue() !== null) {
                $query->where(
                    "comment_text LIKE ?", "%" . $filter->getValue() . "%"
                );
            }

        }

        $query->order("comment_date DESC");

        return $query;
    }

    /**
     * @param Filter[] $filter
     * @return int
     */
    public function getCommentFilteredCount(array $filter): int
    {
        return count($this->getCommentQuery($filter)->fetchAll());
    }

    /**
     * @param Filter[] $filter
     * @param int $offset
     * @param int $limit
     * @return ScreenComment[]
     */
    public function getCommentFiltered(array $filter, int $offset, int $limit): array
    {
        $query = $this->getCommentQuery($filter);
        $query->limit($limit, $offset);

        $data = [];

        foreach ($query->fetchAll() as $row)
            $data[] = ScreenComment::fromArray($row->toArray());

        return $data;
    }

    public function remove(int $id): void
    {
        $this->connection->query("DELETE FROM screenshot_comment WHERE screenshot = ?", $id);
        $this->connection->query("DELETE FROM screenshot_images WHERE id = ?", $id);
    }


    public function increaseViews(int $screenshotId): void
    {
        $this->connection->query("UPDATE screenshot_images SET views = views + 1 WHERE id = ?", $screenshotId);

    }

}
