<?php


namespace App\Controls\Quests;


use App\Model\Endor\Quests\QuestRepository;
use Nette\ComponentModel\IContainer;
use Ublaboo\DataGrid\DataGrid;

class QuestGrid extends DataGrid
{

    public function __construct(
        private QuestRepository $questRepository
    )
    {
        $this->setDataSource($questRepository);
        $this->configure();
        parent::__construct();
    }

    private function configure(): void
    {
        $this->addColumnNumber("Name", "#");
    }

}
