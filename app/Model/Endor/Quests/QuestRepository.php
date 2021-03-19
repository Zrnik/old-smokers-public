<?php


namespace App\Model\Endor\Quests;


use Nette\Database\Explorer;
use Ublaboo\DataGrid\DataSource\IDataSource;
use Ublaboo\DataGrid\Filter\Filter;
use Ublaboo\DataGrid\Utils\Sorting;
use Zrnik\MkSQL\Updater;
use Zrnik\MkSQL\Utilities\Installable;

class QuestRepository extends Installable implements IDataSource
{

    private int $offset;
    private int $limit;
    /**
     * @var Sorting
     */
    private Sorting $sorting;

    public function __construct(Explorer $explorer)
    {
        parent::__construct($explorer->getConnection()->getPdo());
    }

    function install(Updater $updater): void
    {
        $updater->tableCreate("quests");
    }

    public function getCount(): int
    {
        // Filtered count:

        return 0;
    }

    /**
     * @return array<Quest>
     */
    public function getData(): array
    {
        return [];
    }

    public function filter(array $filters): void
    {
    }

    /**
     * @param array<Filter> $condition
     * @return IDataSource
     */
    public function filterOne(array $condition): IDataSource
    {
        $offset = 0;
        //TODO: filter by condition and set offset

        $this->limit($offset, 1);
        return $this;
    }

    public function limit(int $offset, int $limit): IDataSource
    {
        $this->offset = $offset;
        $this->limit = $limit;
       return $this;
    }

    public function sort(Sorting $sorting): IDataSource
    {
        $this->sorting = $sorting;
        return $this;
    }
}
