<?php


namespace App\Model\Endor;


use Zrnik\Base\Enum;

class Jobs extends Enum
{

    const Wanderer = 0;

    // Warove:
    const Fighter = 10;
    const Berserk = 11;
    const Paladin = 12;
    const Mercenary = 13;

    // Magove (a negr)
    const Mage = 20;
    const AirMage = 21;
    const FireMage = 22;
    const IceMage = 23;
    const EarthMage = 24;

    const Necromancer = 28;

    // Cleric atd:

    const Cleric = 30;
    const Priest = 31;
    const Heretic = 32;

    // Level1:

    const Ranger = 101;
    const Druid = 102;
    const Rogue = 103;

    const Craftsman = 200;
}
