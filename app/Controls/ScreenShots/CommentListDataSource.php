<?php


namespace App\Controls\ScreenShots;


use App\Model\ScreenShots\ScreenComment;
use App\Model\ScreenShots\ScreenShotRepository;
use JetBrains\PhpStorm\Pure;
use Ublaboo\DataGrid\DataSource\IDataSource;
use Ublaboo\DataGrid\Filter\Filter;
use Ublaboo\DataGrid\Utils\Sorting;

class CommentListDataSource implements IDataSource
{
    /**
     * @var Filter[]
     */
    private array $filter;

    private int $offset;
    private int $limit;

    public function __construct(
        private ScreenShotRepository $screenShotRepository
    )
    {
    }

    #[Pure]
    public function getCount(): int
    {
        return $this->screenShotRepository->getCommentFilteredCount($this->filter);
    }

    /**
     * @return ScreenComment[]
     */
    public function getData(): array
    {
        // TODO: Implement getData() method.

        return $this->screenShotRepository->getCommentFiltered(
            $this->filter, $this->offset, $this->limit
        );
    }

    /**
     * @param array<Filter> $filters
     */
    public function filter(array $filters): void
    {
        $this->filter = $filters;
    }

    /**
     * @param array<mixed> $condition
     * @return IDataSource
     */
    public function filterOne(array $condition): IDataSource
    {
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
        // Nesortujeme tento datagrid! (zatím)
        return $this;
    }
}
