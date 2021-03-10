<?php


namespace App\Controls\ScreenShots;


use App\Model\ScreenShots\ScreenShotRepository;

class CommentListDataGridFactory
{


    public function __construct(
        private ScreenShotRepository $screenShotRepository
    )
    {
    }

    public function create(): CommentListDataGrid
    {
        return new CommentListDataGrid(
            $this->screenShotRepository
        );
    }
}
