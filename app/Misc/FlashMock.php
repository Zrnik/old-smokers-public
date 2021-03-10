<?php


namespace App\Misc;

/**
 * Tahle tžída je zde pro to, aby to správně
 * napovídalo v latte šablonách.
 *
 * Kecám, je to hlavně pro to, aby to v latte
 * šablonách nebyly property proměnné '$flash'
 * podbarvené jako neexistující...
 *
 * Class FlashMock
 * @package App\Misc
 */
class FlashMock
{
    public string $message;
    public string $type;
}
