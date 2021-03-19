<?php


namespace App\Model\Endor\Quests;


class Quest
{

    public int $id;

    public int $parentQuest;

    public string $questName;
    public string $questNpcName;

    public string $questDescriptionHtml;

    /**
     * @var array<QuestStep>
     */
    public array $steps;

    /**
     * @var array<Quest>
     */
    public array $subQuests;

    public int $locationX = 0;
    public int $locationY = 0;

    public int $minLevel = -1;
    public int $maxLevel = -1;

}
