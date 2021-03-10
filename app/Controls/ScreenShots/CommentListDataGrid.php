<?php


namespace App\Controls\ScreenShots;


use App\Model\ScreenShots\ScreenShotRepository;
use Ublaboo\DataGrid\DataGrid;

class CommentListDataGrid extends DataGrid
{

    public function __construct(
        private ScreenShotRepository $screenShotRepository
    )
    {
        parent::__construct();

        //Určitě ne všechny, šak jich je 50 tisic :D
        $this->setItemsPerPageList([
            10,20,50,100
        ], false);



        $this->setDataSource(
            new CommentListDataSource(
                $screenShotRepository
            )
        );

        $this->addColumnNumber("id", "#");


        $this->addColumnText("author", "Author")
            ->setTemplate(
                __DIR__.'/templates/comment-author.latte',
                ["screenShotRepository" => $this->screenShotRepository]
            )
            ->setFilterText();

        $this->addColumnText("text", "Text")
            ->setTemplate(
                __DIR__.'/templates/comment-text.latte'
            )
            ->setFilterText();


        $this->addColumnText("date", "Date")
            ->setTemplate(
                __DIR__.'/templates/comment-date.latte'
            )
            ->setFilterText();

        $this->addAction(
            "Screens:detail","Jít na",
            "Screens:detail", ["id"=>"screenShotId"]);
    }




}
