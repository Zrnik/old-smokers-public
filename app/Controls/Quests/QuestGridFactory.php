<?php


namespace App\Controls\Quests;


use App\Model\Endor\Quests\QuestRepository;

class QuestGridFactory
{

    public function __construct(
        private QuestRepository $questRepository
    )
    {
    }

    public function create(): QuestGrid
    {
        return new QuestGrid($this->questRepository);
    }
}
