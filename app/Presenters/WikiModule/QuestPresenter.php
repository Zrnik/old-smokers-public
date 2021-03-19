<?php

namespace App\Presenters\WikiModule;

use App\Controls\Quests\QuestGrid;
use App\Controls\Quests\QuestGridFactory;
use Nette;
use Nette\DI\Attributes\Inject;

class QuestPresenter extends BaseWikiPresenter
{

    #[Inject]
    public QuestGridFactory $questGridFactory;

    protected function createComponentQuestGrid(): QuestGrid
    {
        return $this->questGridFactory->create();
    }

}
