<?php


namespace App\Model\ScreenShots;


use Brick\DateTime\LocalDateTime;
use Exception;
use finfo;
use JetBrains\PhpStorm\Pure;
use Nette\Caching\Cache;
use Nette\Caching\Storage;
use Nette\Database\Connection;
use Nette\Database\DriverException;
use Nette\DI\Container;
use Nette\Utils\Finder;
use Nette\Utils\Image;
use Nette\Utils\Random;
use Nette\Utils\Strings;
use Throwable;

/**
 * Tenhle skript je fakt hnusnej a nepatří k těm nejlepším,
 * ale je to kandidát na smazání, takže je mi to fuk.
 *
 * @package App\Model\ScreenShots
 */
class OldScreensParser
{


    /**
     * Nějací spammeři na screenshotech, tohle je seznam
     * veci ktere kdyz se objevi ve jmene nebo komentari
     * samotnem, tak to nebudeme ukladat
     *
     * @var string[]
     */
    private static array $SkipUserComments = [
        " &lt;a href=&quot;", //Jakykoliv pokus o odkaz, uz to odstrani asi 10 tisc komentaru :D
    ];


    public static function convertMetaToDatabase(
        Container $container,
        Storage $storage,
        Connection $connection,
        ScreenShotRepository $ssr
    ): void
    {
        $tempDir = $container->parameters["tempDir"] . "/wbm-download/";
        if (!file_exists($tempDir))
            @mkdir($tempDir, 0777, true);

        // dump($tempDir);


        foreach (Finder::findFiles('*.meta.json')->from($tempDir) as $key => $file) {

            $data = @json_decode(strval(file_get_contents($key)), true);

            if ($data === false)
                continue;

            $connection->query("INSERT INTO screenshot_images", [
                "original_id" => $data["id"],
                "uploader_legacy" => $data["author"],
                "title" => $data["title"],
                "image_date" => $data["time"],
            ]);

            $screenId = intval($connection->getInsertId());

            //Ted kdyz mame ID, muzeme prenest obrazek do "dist" slozky!
            // A samozdrejme vytvorit thumbnail!


            $image = Image::fromFile($tempDir . "/" . $data["id"] . ".jpg");

            $dir = dirname($ssr->pathOriginal($screenId, true));
            if (!file_exists($dir))
                @mkdir($dir, 0777, true);


            $image->save($ssr->pathOriginal($screenId, true), 100, Image::JPEG);

            $image->resize(ScreenShotRepository::ThumbnailX, ScreenShotRepository::ThumbnailY, Image::SHRINK_ONLY);

            $image->save($ssr->pathThumbnail($screenId, true), ScreenShotRepository::ImageQuality, Image::JPEG);


            if ($data["comments"] === null) {

                $noCommentsText = 'Nazdárek!' . "\n\n";
                $noCommentsText .= 'K tomuto screenshotu se nepodařilo z Wayback Machine dostat komentáře, proto zde chybí!' . "\n\n";
                $noCommentsText .= 'Pokud by třeba pan Demostenes dovolil, a pan Pirožek byl ochotný... ';
                $noCommentsText .= 'co to kecám, určitě nebyli. Komentáře prostě nejsou a ani s největší pravděpodobností ' .
                    'nebudou, to vám ale nebrání napsat další :)';

                $connection->query("INSERT INTO screenshot_comment", [
                    "screenshot" => $screenId,
                    "author_legacy" => "Automatický komentář",
                    "comment_date" => $data["time"],
                    "comment_text" => $noCommentsText,
                ]);
            } else {

                foreach ($data["comments"] as $comment) {

                    $connection->query("INSERT INTO screenshot_comment", [
                        "screenshot" => $screenId,
                        "original_id" => $comment["originalId"],
                        "author_legacy" => $comment["legacyAuthor"],
                        "comment_date" => $comment["date"],
                        "comment_text" => $comment["text"],
                    ]);

                }

            }

        }

    }


    public static function importWaybackMachineMeta(
        Container $container,
        Storage $storage,
        Connection $connection,
        ScreenShotRepository $ssr
    ): void
    {

        $tempDir = $container->parameters["tempDir"] . "/wbm-download/";
        if (!file_exists($tempDir))
            @mkdir($tempDir, 0777, true);

        dump($tempDir);

        $start = 2018;
        $startTimestamp = mktime(0, 0, 0, 0, 0, $start);

        // Meta zde:

        for ($timestamp = $startTimestamp; $timestamp <= time(); $timestamp += strtotime("+1 month", 0)) {
            for ($offset = 0; $offset <= 84; $offset += 12) {
                //echo "Looking for offset: " . $offset;


                $metaContainingSite = 'http://screens.endor-reborn.cz/?offset=' . $offset;

                if ($offset === 0)
                    $metaContainingSite = 'http://screens.endor-reborn.cz/';

                if($timestamp === false)
                    $timestamp = intval($timestamp);

                $archiveData = json_decode(self::content(
                    'http://archive.org/wayback/available?url=' . $metaContainingSite . "&timestamp=" . date("Ymd", $timestamp)
                ), true);

                if (array_key_exists("archived_snapshots", $archiveData))
                    if (array_key_exists("closest", $archiveData["archived_snapshots"]))
                        if (array_key_exists("url", $archiveData["archived_snapshots"]["closest"])) {
                            self::parseMetaFromWBMachine(
                                $archiveData["archived_snapshots"]["closest"]["url"],
                                $tempDir
                            );
                        }


            }
        }


        // Zde se pokusíme stáhnout obrázek a komentáře.

        foreach (Finder::findFiles('*.meta.json')->from($tempDir) as $key => $file) {

            $meta = @json_decode(strval(file_get_contents($key)), true);

            if ($meta === false)
                continue;

            $id = $meta["id"];
            $imageName = $tempDir . "/" . $id . ".jpg";

            if (!array_key_exists("comments", $meta)) {
                echo 'Fetching comments of ' . $id . ' ... ';

                $detailUrl = 'https://screens.endor-reborn.cz/detail.php?id=' . $id;

                $archiveData = json_decode(self::content(
                    'http://archive.org/wayback/available?url=' . $detailUrl
                ), true);

                $comments = null;
                if (array_key_exists("archived_snapshots", $archiveData)) {
                    if (array_key_exists("closest", $archiveData["archived_snapshots"])) {
                        if (array_key_exists("url", $archiveData["archived_snapshots"]["closest"])) {
                            $detailContent = self::content(
                                $archiveData["archived_snapshots"]["closest"]["url"]
                            );


                            $remoteCommentList = explode('<div class="comment">', $detailContent);
                            unset($remoteCommentList[0]);

                            $comments = [];

                            foreach ($remoteCommentList as $remoteComment) {

                                $originalId = explode('<a name="c-', $remoteComment);
                                $originalId = intval(explode('">', $originalId[1])[0]);

                                $commentAuthor = explode('<strong>', $remoteComment);
                                $commentAuthor = explode('</strong>', $commentAuthor[1])[0];

                                $commentText = explode('<div class="c-body">', $remoteComment);
                                $commentText = explode('</div>', $commentText[1])[0];

                                $commentDate = explode('</strong>,', $remoteComment);
                                $commentDate = trim(explode('</div>', $commentDate[1])[0]);

                                [$date, $time] = explode(" ", $commentDate);

                                [$day, $month, $year] = explode(".", $date);
                                [$hour, $minute] = explode(":", $time);

                                $realTime = LocalDateTime::of(
                                    intval($year),
                                    intval($month),
                                    intval($day),
                                    intval($hour),
                                    intval($minute),
                                    intval(0)
                                );

                                /*dump($originalId);
                                 dump($commentAuthor);
                                 dump($commentText);
                                 dump($commentDate);
                                 dump($realTime->jsonSerialize());

                                 dumpe($remoteComment);*/

                                $commentData = [
                                    "originalId" => $originalId,
                                    "legacyAuthor" => $commentAuthor,
                                    "text" => $commentText,
                                    "date" => $realTime->jsonSerialize(),

                                ];

                                $comments[] = $commentData;

                                // dump($commentData);

                            }


                        }
                    }
                }

                $meta["comments"] = $comments;

                //$meta = json_decode(file_get_contents($key), true);
                file_put_contents($key, json_encode($meta));

                //dump($archiveData);

                echo 'Done!' . PHP_EOL;
            }


            if (!file_exists($imageName)) {
                $originalImage = 'http://screens.endor-reborn.cz/img/' .
                    Strings::padLeft(strval($id), 5, "0") .
                    '.';

                $content = self::content($originalImage . "jpg");
                if ($content === '')
                    $content = self::content($originalImage . "png");
                if ($content === '')
                    $content = self::content($originalImage . "gif");

                if ($content !== '') {

                    echo 'Downloading ... ' . $originalImage;
                    $tmpName = $tempDir . "/" . Random::generate(25);
                    //echo $originalImage.PHP_EOL;
                    file_put_contents($tmpName, $content);

                    $img = Image::fromFile($tmpName);
                    $img->save($imageName, ScreenShotRepository::ImageQuality, Image::JPEG);

                    while (file_exists($tmpName))
                        unlink($tmpName);

                    echo ' ... Done!' . PHP_EOL;
                }
            }


            // if(!file_exists())

            //dumpe($meta);


        }


    }

    /**
     * @param string $url
     * @param string $tempDir
     */
    private static function parseMetaFromWBMachine(string $url, string $tempDir): void
    {
        $content = self::content($url);

        $screenshots = explode('<div class="picTn">', $content);
        unset($screenshots[0]);

        foreach ($screenshots as $screenshot) {
            $id = explode('"><img', $screenshot);
            $id = intval(str_replace('<a href="detail.php?id=', "", $id[0]));

            $metaFile = $tempDir . "/" . $id . ".meta.json";

            if (file_exists($metaFile))
                continue;

            dump($screenshot);

            $author = explode('<span class="author">', $screenshot);
            $author = explode(':</span>', $author[1])[0];

            $title = explode('<span class="title">', $screenshot);
            $title = explode('</span>', $title[1])[0];


            $uploadTime = explode('<span class="time">', $screenshot);
            $uploadTime = explode('</span>', $uploadTime[1])[0];

            [$date, $time] = explode(" ", $uploadTime);

            [$day, $month, $year] = explode(".", $date);
            [$hour, $minute] = explode(":", $time);

            $realTime = LocalDateTime::of(
                intval($year),
                intval($month),
                intval($day),
                intval($hour),
                intval($minute),
                intval(0)
            );

            $retrievedMeta = [
                "id" => $id,
                "author" => $author,
                "title" => $title,
                "time" => $realTime->jsonSerialize()
            ];


            file_put_contents($metaFile, json_encode($retrievedMeta));
        }


        echo PHP_EOL;
    }

    public static function importWaybackMachine(
        Container $container,
        Storage $storage,
        Connection $connection,
        ScreenShotRepository $ssr
    ): void
    {
        $tempDir = $container->parameters["tempDir"] . "/wbm-download/";
        if (!file_exists($tempDir))
            @mkdir($tempDir, 0777, true);

        for ($i = 0; $i <= 70; $i++) {
            $realFileNameInTmpDir = $tempDir . "/" . $i . ".jpg";

            // Neexistuje-li obrazek, stahneme ho
            if (!file_exists($realFileNameInTmpDir)) {
                $originalImage = 'http://screens.endor-reborn.cz/img/' . Strings::padLeft(strval($i), 5, "0") . '.';

                $content = self::content($originalImage . "jpg");
                if ($content === '')
                    $content = self::content($originalImage . "png");
                if ($content === '')
                    $content = self::content($originalImage . "gif");

                if ($content !== '') {
                    echo 'Downloading ... ' . $originalImage;
                    $tmpName = $tempDir . "/" . Random::generate(25);
                    //echo $originalImage.PHP_EOL;
                    file_put_contents($tmpName, $content);

                    $img = Image::fromFile($tmpName);
                    $img->save($realFileNameInTmpDir, ScreenShotRepository::ImageQuality, Image::JPEG);

                    while (file_exists($tmpName))
                        unlink($tmpName);

                    echo ' ... Done!' . $originalImage;
                }

                //A prevedeme na JPG!
            }

            if (file_exists($realFileNameInTmpDir)) {
                $id = intval(str_replace(".jpg", "", basename($realFileNameInTmpDir)));
                echo "Searching for metadata of: " . $id . PHP_EOL;

                $archiveData = json_decode(self::content(
                    'http://archive.org/wayback/available?url=http://screens.endor-reborn.cz/detail.php?id=' . $id
                ), true);


                echo PHP_EOL;

            }


            //

            // file_put_contents("")


        }

    }

    /**
     * @param Storage $storage
     * @param Connection $connection
     * @param ScreenShotRepository $ssr
     * @throws Throwable
     */
    public static function import(
        Storage $storage,
        Connection $connection,
        ScreenShotRepository $ssr
    ): void
    {
        $cache = new Cache($storage, 'latest_screenshots');

        $cache->load("lates_remote_screenshot", function (&$dependencies) use ($ssr, $connection) {
            $dependencies[Cache::EXPIRE] = "0 second";

            echo "Max ExecTime: " . ini_get("max_execution_time") . PHP_EOL;

            $latestImageId = self::getLatestImageId();

            // bdump($latestImageId, "Latest Image");

            $lastImportedImg = $ssr->getLatestImportId();

            //bdump($lastImportedImg, "Latest Import");

            for ($i = $lastImportedImg + 1; $i <= $latestImageId; $i++) {

                // Tak to rozjedeme! :)
                /*if (microtime(true) > $_SERVER["REQUEST_TIME"] + 10)
                     break;*/


                echo sprintf("Importing '%s' (%s): ", $i, round(microtime(true) - $_SERVER["REQUEST_TIME"])) . PHP_EOL;

                $image = self::downloadImage($i, $ssr);

                if ($image === null)
                    continue;

                //Tohle je neco jinneho nez bude delat repozitar, ulozim to zde rucne.
                $newScreenId = -1;
                try {
                    echo "I-";
                    $connection->query("INSERT INTO screenshot_images", $image->toArray());
                    echo "I";

                    $newScreenId = intval($connection->getInsertId());

                    // Jelikoz jsou obrazky ulozene podle endorskeho id a ne databazoveho ID, musime je jeste prejmenovat:
                    rename($ssr->pathOriginal($i, true), $ssr->pathOriginal($newScreenId, true));
                    rename($ssr->pathThumbnail($i, true), $ssr->pathThumbnail($newScreenId, true));


                    $insertData = [];

                    foreach ($image->comments as $comment) {
                        $comment->screenShotId = $newScreenId;

                        try {
                            $insertData[] = $comment->toArray();
                        } catch (DriverException) {
                            echo "UNABLE TO IMPORT COMMENT: ";
                            dump($comment);
                        }

                    }

                    if (count($insertData) > 0) {
                        echo "C-";
                        $connection->query(
                            "INSERT INTO screenshot_comment",
                            $insertData
                        );
                        echo "C";
                    }

                } catch (DriverException $t) {
                    $connection->query(
                        "DELETE FROM screenshot_comment WHERE screenshot = ?",
                        $newScreenId
                    );

                    $connection->query(
                        "DELETE FROM screenshot_images WHERE id = ?",
                        $newScreenId
                    );

                    echo PHP_EOL . PHP_EOL . "EXECUTION STOPPED: " . $t->getMessage();
                    //throw $t;
                    die();
                }

                echo PHP_EOL . sprintf('Image with ID "%s" (%s) IMPORTED!', $i, $image->title) . PHP_EOL;

                //bdump($image, "Image Id: ".$newScreenId);

            }


            return true;
        });


    }

    private static function getLatestImageId(): int
    {
        $homepage = self::content("http://screens.endor.cz/");

        $spl = explode("<a href=\"detail.php?id=", $homepage);
        unset($spl[0]);
        return intval(explode("\"", $spl[1])[0]);
    }

    private static function content(string $string): string
    {
        $content = @file_get_contents($string);
        if ($content === false)
            return '';
        return $content;
    }

    /**
     * @param int $id
     * @param ScreenShotRepository $ssr
     * @return Screen|null
     * @throws Exception
     */
    private static function downloadImage(int $id, ScreenShotRepository $ssr): ?Screen
    {

        $content = static::content("http://screens.endor.cz/detail.php?id=" . $id);

        if (Strings::contains(self::convertEncoding($content, true) ?? "Obrázek nenalezen.", "Obrázek nenalezen.")) {
            echo sprintf('Image with ID "%s" not found!', $id) . PHP_EOL;
            // bdump("NOT FOUND!", "Importing ID: " . $id);
            return null;
        }

        echo "i";

        $screenshot = Screen::create();

        $screenshot->originalId = $id;

        //bdump($content);

        $picDetail = explode('<div class="picDetail">', $content);
        $picDetail = explode('</div>', $picDetail[1])[0];

        //region Screenshot Author & Name

        $header = explode('<h1>', $picDetail);
        $header = explode('</h1>', $header[1])[0];

        $Author = explode(":", $header)[0];

        $screenshot->legacyUploader = self::convertEncoding(trim($Author)) ?? 'Unknown Author';


        $Title = trim(str_replace("[Beginning]" . $Author . ":", "", "[Beginning]" . $header));

        $Title = self::convertEncoding($Title) ?? '';

        $screenshot->title = trim($Title);

        //endregion

        //region ScreenShot DateTime

        /**
         * Pro datum potřebujeme záznam screenshotu ze seznamu,
         * protože na detailu ten datum nikde napsaný není!
         */

        $offsetLink = explode("<span class=\"pg\"><a href=\"", $content)[1];
        $offsetLink = explode("\"", $offsetLink)[0];

        $screenshot->date = self::getDateOf($id, "http://screens.endor.cz/" . $offsetLink);

        //endregion

        //region ScreenShot Image

        $imageLocation = explode('<img src="', $picDetail);
        $imageLocation = explode('"', $imageLocation[1])[0];
        $imageLocation = 'http://screens.endor.cz/' . $imageLocation;

        $imageContent = static::content($imageLocation);

        $finfo = new finfo(FILEINFO_MIME);
        $mimeType = $finfo->buffer($imageContent);

        // Takše, mise za prostor nám káže, převést
        // vše na JPG o nevalné kvalitě :D

        $downloadPath = $ssr->pathOriginal($id, true) . ".original";
        $originalPath = $ssr->pathOriginal($id, true);
        $thumbnailPath = $ssr->pathThumbnail($id, true);

        if (!file_exists(dirname($downloadPath)))
            @mkdir(dirname($downloadPath), 0777, true);

        echo PHP_EOL . "MimeType: " . $mimeType . PHP_EOL;

        // Uložit Original:
        file_put_contents($downloadPath, $imageContent);


        echo PHP_EOL . "Download Velikost: " . self::niceSize(intval(filesize($downloadPath))) . PHP_EOL;

        $image = Image::fromFile($downloadPath);
        $image->save($originalPath, ScreenShotRepository::ImageQuality, Image::JPEG);

        echo "Ulozena Velikost: " . self::niceSize(intval(filesize($originalPath))) . PHP_EOL;

        $image = Image::fromFile($originalPath);
        $image->resize(
            ScreenShotRepository::ThumbnailX,
            ScreenShotRepository::ThumbnailY,
            Image::SHRINK_ONLY
        );

        $image->save(
            $thumbnailPath,
            ScreenShotRepository::ThumbnailQuality,
            Image::JPEG
        );

        echo "Thumbnail Velikost: " . self::niceSize(intval(filesize($thumbnailPath))) . PHP_EOL;

        unlink($downloadPath);
        unset($image);


        // $screenshot->imgType = $mimeType;
        /*  $screenshot->imgData = base64_encode($imageContent);*/

        //endregion

        //region ScreenShot Comments

        $comments = explode('<div class="comment">', $content);
        unset($comments[0]);

        foreach ($comments as $commentData) {

            $comment = ScreenComment::create();

            echo "c";

            //Original Id
            $originalId = explode('<a href="#c-', $commentData);
            $originalId = intval(explode('"', $originalId[1])[0]);
            $comment->originalId = $originalId;

            // Author
            $author = explode('<strong>', $commentData);
            $author = explode('</strong>', $author[1])[0];
            $comment->legacyAuthor = self::convertEncoding($author) ?? '';

            foreach (static::$SkipUserComments as $skippableOffense) {
                if (str_contains($comment->legacyAuthor, $skippableOffense)) {
                    echo "SKIPPED COMMENT OF " . $comment->legacyAuthor . PHP_EOL;
                    continue;
                }
            }


            // Text
            $text = explode('<div class="c-body">', $commentData);
            $text = explode('</div>', $text[1])[0];

            $oText = $text;

            $text = self::convertEncoding($text);

            if ($text === null)
                throw new Exception("Unreadable comment of " . $comment->legacyAuthor . " " . $oText);


            $comment->text = $text;

            //echo $text;

            $skip = false;
            foreach (static::$SkipUserComments as $skippableOffense) {
                if (str_contains($comment->text, $skippableOffense)) {
                    echo "SKIPPED COMMENT OF " . $comment->legacyAuthor . PHP_EOL;
                    $skip = true;
                    break;
                }
            }

            if ($skip)
                continue;

            // Date
            $originalDate = explode('</strong>,', $commentData);
            $originalDate = trim(explode('</div>', $originalDate[1])[0]);

            [$date, $time] = explode(" ", $originalDate);

            [$day, $month, $year] = explode(".", $date);
            [$hour, $minute] = explode(":", $time);

            $comment->date = LocalDateTime::of(
                intval($year), intval($month), intval($day), intval($hour), intval($minute)
            );

            $screenshot->comments[] = $comment;
        }

        //endregion

        //bdump($screenshot, "Importing ID: " . $id);

        return $screenshot;
    }

    private static function getDateOf(int $id, string $list): LocalDateTime
    {
        $content = static::content($list);

        $pictures = explode("<div class=\"picTn\">", $content);

        foreach ($pictures as $pictureData) {
            if (str_starts_with(trim($pictureData), "<a href=\"detail.php?id=" . $id . "\">")) {

                $time = explode("<span class=\"time\">", $pictureData)[1];
                $time = explode("</span>", $time)[0];

                [$date, $time] = explode(" ", $time);

                [$hour, $minute] = explode(":", $time);
                [$day, $month, $year] = explode(".", $date);

                //bdump($date);

                return LocalDateTime::of(
                    intval($year),
                    intval($month),
                    intval($day),
                    intval($hour),
                    intval($minute)
                );
            }

        }


        //bdump($pictures);

        throw new Exception("Image '" . $id . "' not found!");
        //return LocalDateTime::of(12,1,1,15,0,0);
    }

    private static function convertEncoding(
        string $invalidEncoding,
        bool $returnOriginalOnError = false
    ): ?string
    {
        $do = mb_detect_order();
        if (is_bool($do))
            $do = [];

        $encoding = mb_detect_encoding(
            $invalidEncoding,
            $do,
            true
        );

        if ($encoding === false)
            $encoding = 'windows-1250'; // Na webu je to tak napsané! v metadatech!

        $conv = @iconv(
            $encoding,
            "UTF-8",
            $invalidEncoding
        );

        if ($conv === false)
            $conv = null;

        return $conv;
    }

    #[Pure]
    private static function niceSize(int $byteSize): string
    {
        $units = array('B', 'kB', 'MB', 'GB', 'TB'); // ...etc

        for ($i = 0, $size = $byteSize; $size > 1024; $size = $size / 1024)
            $i++;

        return number_format($size, 2) . ' ' . $units[min($i, count($units) - 1)];
    }


}
