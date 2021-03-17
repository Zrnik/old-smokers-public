<?php


namespace App\Model\UltimaOnline\Text;


use App\Model\UltimaOnline\Text\Font\FontType;
use Nette\Utils\Image;
use Nette\Utils\Strings;
use Nette\Utils\UnknownImageFileException;

class TextRenderer
{
    const RowHeight = 20;
    const SpaceWidth = 7;

    /**
     * @param string $text
     * @param string $rgbColor
     * @return Image
     * @throws UnknownImageFileException
     */
    public static function render(string $text, string $rgbColor): Image
    {
        $rgbColor = Strings::webalize($rgbColor);
        [$hexR, $hexG, $hexB] = str_split($rgbColor, 2);

        $textColor = Image::rgb(
            intval(hexdec($hexR)),
            intval(hexdec($hexG)),
            intval(hexdec($hexB)),
        );

        $fontType = self::getFontTypeFromColor($textColor);

        $characterSpace = $fontType === FontType::Bordered ? -1 : 1;


        /**
         * @var array<string, Image>
         */
        $resources = [];
        $totalWidth = 0;

        foreach (self::str_split_unicode($text) as $char) {

            if (!array_key_exists($char, $resources) && $char != " ") {
                $resources[$char] = self::loadCharacter($char, $fontType);
            }


            if (array_key_exists($char, $resources) && $char != " ")
                $totalWidth += $resources[$char]->width + $characterSpace;

            if ($char === " ")
                $totalWidth += self::SpaceWidth;
        }

        $totalWidth -= $characterSpace;

        $Image = Image::fromBlank($totalWidth, self::RowHeight, Image::rgb(0, 0, 0, 127));

        $OffsetX = 0;
        foreach (self::str_split_unicode($text) as $char) {
            if ($char === " ") {
                $OffsetX += (self::SpaceWidth + $characterSpace);
                continue;
            }

            $Image->place($resources[$char], $OffsetX);
            $OffsetX += $resources[$char]->width + $characterSpace;
        }


        // Re-Colorze Text
        $keyColor = Image::rgb(128, 0, 0);
        for ($x = 0; $x < $Image->width; $x++) {
            for ($y = 0; $y < $Image->height; $y++) {
                if ($Image->colorsForIndex($Image->colorAt($x, $y)) === $keyColor) {
                    $Image->setPixel($x, $y, $textColor);
                }
            }
        }

        return $Image;
    }

    /**
     * @param mixed $char
     * @param int $fontType
     * @return Image
     * @throws UnknownImageFileException
     */
    private static function loadCharacter(mixed $char, int $fontType): Image
    {
        return Image::fromFile(__DIR__ . '/Font/' . FontType::getName($fontType) . '/char_' . self::uniord($char) . '.png');
    }


    /**
     * @param string $str
     * @param int $l
     * @return array<string>
     */
    private static function str_split_unicode(string $str, int $l = 0): array
    {
        if ($l > 0) {
            $ret = array();
            $len = mb_strlen($str, "UTF-8");
            for ($i = 0; $i < $len; $i += $l) {
                $ret[] = mb_substr($str, $i, $l, "UTF-8");
            }
            return $ret;
        }

        $split = preg_split("//u", $str, -1, PREG_SPLIT_NO_EMPTY);

        if (is_bool($split))
            return [];

        return $split;
    }

    /**
     * @param array<mixed> $textColor
     * @return int
     */
    private static function getFontTypeFromColor(array $textColor): int
    {
        if (
            // Pokud jsou všechny barvy pod 50, dáme písmo normal...
            $textColor["red"] <= 50
            && $textColor["green"] <= 50
            && $textColor["blue"] <= 50
        )
            return FontType::Normal;

        return FontType::Bordered;
    }

    private static function uniord(string $s): string
    {
        if ($s === "")
            return "-1";

        $iconv = iconv('UTF-8', 'UCS-4LE', $s);

        if (is_bool($iconv))
            return "-1";

        $unpack = unpack(
            'V', $iconv
        );

        if (is_bool($unpack))
            return "-1";

        return $unpack[1];
    }

}
