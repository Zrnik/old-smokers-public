<?php

namespace App\Controls\Application;

use App\Model\Applications\Application;
use App\Model\Applications\ApplicationRepository;
use App\Model\Discord\Connection\Guilds;
use App\Model\Discord\Connection\Messages;
use App\Model\Discord\Exceptions\MessageException;
use App\Model\Security\DiscordUser;
use App\Model\Security\DiscordUserRepository;
use Nette\Application\AbortException;
use Nette\Application\UI\Control;
use Nette\Application\UI\InvalidLinkException;
use Nette\Application\UI\Presenter;
use Nette\Utils\Html;
use Nette\Utils\JsonException;
use Throwable;

/**
 * Ok, tahle komponenta je sračka, uznávám,
 * ale byl to jen prototyp a protože jsme
 * (prozatím) zavrhli to, že lidé musí psát
 * přihlášky, tak ji zatím nebudu přepisovat.
 *
 * Ale samozdřejmě by to chtělo rozsekat na
 * subkomponenty jako např progress bar,
 * seznam hlasů a podobné.
 *
 * Class VotingForm
 * @package App\Controls\Application
 */
class VotingForm extends Control
{

    private ?Application $application;

    public function __construct(
        private int $applicationId,
        private DiscordUser $user,
        private Guilds $guilds,
        private Messages $messages,
        private ApplicationRepository $applicationRepository,
        private DiscordUserRepository $discordUserRepository
    )
    {
        $this->application = $this->applicationRepository->getApplication($applicationId);

    }

    /**
     * @throws Throwable
     */
    public function render(): void
    {
        if($this->application === null)
        {
            echo "No application.";
            return;
        }

        if (!$this->application->votingStarted) {

            echo "<b>Hlasování ještě nebylo zahájeno.</b>";

            if ($this->user->isDeputy()) {
                echo Html::el("a")
                    ->href($this->link("startVoting!"))
                    ->class("block btn btn-sm text-center bg-green-300 hover:bg-green-400 text-green-700 mt-5")
                    ->setText("Zahájit hlasování!")
                    ->render();
            }

        } else {

            // Zobrazím

            // Je li hlasování ukončeno, zobrazíme konečné rozhodnutí!
            if ($this->application->isResolved())
                echo $this->getResults()->render();

            // Zacalo-li hlasovani, ukazu progressbary vsem
            // if($this->application->votingStarted) // Tohle us osefilo nadrazene IF!
            echo $this->progressBars()->render();

            // Není li rozhodnuto, a mohu hlasovat, zobrazám hlasovací tlačítka
            if (!$this->application->isResolved() && $this->user->canVote())
                echo $this->voteButtons()->render();

            //Deputy může i vidět kdo pro koho hlasoval!
            if ($this->user->isDeputy())
                echo $this->getVoters()->render();


        }
    }

    /**
     * @return Html<mixed>
     * @throws Throwable
     */
    private function progressBars(): Html
    {
        if ($this->application !== null) {
            $result = Html::el();

            $result->addHtml(
                Html::el("div")
                    ->setText(
                        "Pro přijetí do guildy je potřeba získat nadpoloviční většinu."
                    )
                    ->addHtml(Html::el("div")->class(" text-sm text-gray-400")
                        ->setText("Potřeba hlasů: " . $this->application->votesRequired)
                    )
            );

            if ($this->application->votesRequired === 0) {
                //Nechceme přece dělit nulou!
                $this->application->votesRequired = 1;
                $this->applicationRepository->save($this->application);
            }


            $voteBars = Html::el("table")->class("table w-full mb-2");

            $hlasyPro = 0;
            $hlasyProti = 0;

            foreach ($this->application->memberVotes as $vote) {
                if ($vote->agreed)
                    $hlasyPro++;
                if (!$vote->agreed)
                    $hlasyProti++;
            }


            $voteBars->addHtml(
                $this->createBar("Pro:", "bg-green-700", ($hlasyPro / $this->application->votesRequired) * 100, $hlasyPro)
            );

            $voteBars->addHtml(
                $this->createBar("Proti:", "bg-red-800", ($hlasyProti / $this->application->votesRequired) * 100, $hlasyProti)
            );


            $result->addHtml($voteBars);


            return $result;
        }


        return Html::el();
    }

    /**
     * @param string $Text
     * @param string $BarClass
     * @param float $Percentage
     * @param int $voteCount
     * @return Html<mixed>
     */
    private function createBar(string $Text, string $BarClass, float $Percentage, int $voteCount): Html
    {
        return
            Html::el("tr")
                ->addHtml(
                    Html::el("td")
                        ->style("width: 75px")
                        ->class("text-right pr-2")
                        ->setText($Text)
                )
                ->addHtml(
                    Html::el("td")->class("bg-gray-600 bg-opacity-25")
                        ->addHtml(
                            Html::el("div")->class($BarClass)
                                ->style("width", $Percentage . "%")
                                ->addHtml("&nbsp;")
                        )
                )
                ->addHtml(
                    Html::el("td")
                        ->class("text-center")
                        ->style("width: 50px")
                        ->setText($voteCount . "x")
                );


    }

    /**
     * @return Html<mixed>
     * @throws InvalidLinkException
     */
    private function voteButtons(): Html
    {
        $buttons = Html::el();

        if($this->application === null)
            return $buttons;

        $voteOf = $this->application->getVoteOf($this->user->getId());

        if ($voteOf === null) {
            $buttons->addHtml(
                Html::el("div")->class("my-2 h4")->setText("Váš hlas ještě nebyl započítán!")
            );

        } else {
            $buttons->addHtml(
                Html::el("div")
                    ->addText("Hlasovali jste ")
                    ->addHtml(Html::el("b")
                        ->setText($voteOf->agreed ? "PRO" : "PROTI")
                        ->class($voteOf->agreed ? "text-green-500" : "text-red-500")
                    )
                    ->addText(" přijetí.")
            );
        }


        $buttons->addHtml(
            Html::el("a")->href(
                $this->link("vote!", ["agreed" => 1])
            )->setText("Hlasovat PRO přijetí")
                ->class("btn rounded-lg rounded-r-none bg-green-300 hover:bg-green-400 text-green-800 w-1/2")
        );


        $buttons->addHtml(
            Html::el("a")->href(
                $this->link("vote!", ["agreed" => 0])
            )->setText("Hlasovat PROTI přijetí")
                ->class("btn rounded-lg rounded-l-none bg-red-300 hover:bg-red-400 text-red-800 w-1/2")
        );


        return $buttons;
    }

    /**
     * @throws Throwable
     * @throws AbortException
     * @throws JsonException
     */
    public function handleVote(): void
    {
        if($this->application === null)
            return;

        if (!$this->user->canVote() && !$this->application->isResolved())
            return;

        $key = $this->lookupPath(Presenter::class) . "-agreed";

        $agreed = boolval($_GET[$key]);

        $this->application->castVote($this->user->getId(), $agreed);
        $this->applicationRepository->save($this->application);

        if ($this->application->isResolved()) {

            $userLink = $this->discordUserRepository->getUserLink(
                $this->application->ownerId
            );

            // Tímto hlasem se hlasování ukončí!

            // Odešleme oznámení na server
            $this->messages->sendNotification(
                sprintf(
                    "Hlasování o přihlášce uživatele " .
                    " %s skončilo. Výsledek je:\n%s",
                    $userLink, $this->messages->successIndicator(
                    $this->application->isAccepted(),
                    $this->application->isAccepted() ?
                        "Přijat!" :
                        "Nepřijat!"
                )
                )
            );

            // A taky samotnému uživateli
            try {
                $this->messages->sendTo(
                    $this->application->ownerId,
                    sprintf(
                        "Hlasování o vaší přihlášce bylo ukončeno! Výsledek:\n%s",
                        $this->messages->successIndicator(
                            $this->application->isAccepted(),
                            $this->application->isAccepted() ?
                                "Přijat!" :
                                "Nepřijat!"
                        )
                    )
                );
            } catch (MessageException $e) {
                //Pokud to lze :)
            }


        }


        // Je li nyni hlasování ukončeno, informujeme o tom lidi!


        $this->redirect("this");
    }

    /**
     * @return Html<mixed>
     * @throws Throwable
     */
    private function getVoters(): Html
    {
        if($this->application === null)
            return Html::el();

        $voterHtml = Html::el("table")->class("table w-full");

        foreach ($this->application->memberVotes as $memberVote) {
            $voter = $this->guilds->getMember($memberVote->discordId);

            if ($voter !== null) {

                $voterHtml->addHtml(
                    Html::el("tr")
                        ->addHtml(
                            Html::el("td")
                                ->class("font-bold text-right w-1/2 pr-2 text-bold")
                                ->style("color", $voter->getHexColor())
                                ->setText($voter->getNickname() . ":")
                        )
                        ->addHtml(
                            Html::el("td")
                                ->class("font-bold text-left w-1/2 " . ($memberVote->agreed ? "text-green-500" : "text-red-500"))
                                ->addText($memberVote->agreed ? "PRO" : "PROTI")
                                ->addText(" přijetí")
                        )
                );
            } else {
                $voterHtml->addHtml(
                    Html::el("tr")
                        ->addHtml(
                            Html::el("td")
                                ->class("font-bold text-right w-1/2 pr-2 text-bold")
                                ->setText("Unknown User:")
                        )
                        ->addHtml(
                            Html::el("td")
                                ->class("font-bold text-left w-1/2 " . ($memberVote->agreed ? "text-green-500" : "text-red-500"))
                                ->addText($memberVote->agreed ? "PRO" : "PROTI")
                                ->addText(" přijetí")
                        )
                );
            }
        }

        return $voterHtml;
    }


    /**
     * @throws AbortException
     * @throws JsonException
     * @throws MessageException
     * @throws InvalidLinkException
     * @throws Throwable
     */
    public function handleStartVoting(): void
    {
        if($this->application === null)
            return;

        $owner = $this->discordUserRepository->getUser($this->application->ownerId);
        $presenter = $this->getPresenter();
        if ($owner !== null && $this->application !== null && $this->user->isDeputy() && $presenter !== null) {


            // Přihláška existuje, a já jsem oprávněn to začít:
            $this->application->votingStarted = true;
            $this->applicationRepository->save($this->application);

            $applicationLink = $presenter->link(
                "//Application:detail",
                ["id" => $this->applicationId]
            );

            $userLink = $this->discordUserRepository->getUserLink(
                $this->application->ownerId
            );


            $this->messages->sendNotification(
                sprintf(
                    "Hlasování o přijetí uživatele **%s** bylo ZAHÁJENO!" .
                    "\n" . $applicationLink,
                    $userLink
                )
            );

            try {
                $this->messages->sendTo(
                    $this->application->ownerId,
                    "U vaší přihlášky bylo zahájeno hlasování o přijetí!\n" .
                    $applicationLink
                );
            } catch (MessageException $e) {
                // a nebo nic...
            }

            $this->flashMessage(
                "Hlasování zahájeno!",
                "bg-green-300 text-green-700 border-green-700"
            );

            $this->redirect("this");
        }
    }

    /**
     * @return Html<mixed>
     */
    private function getResults(): Html
    {
        if($this->application === null)
            return Html::el();

        return Html::el("div")->class("p-3 bg-gray-600 bg-opacity-25")
            ->addText("Hlasování bylo ukončeno s výsledkem:")
            ->addHtml(
                Html::el("div")
                    ->class("text-center h3 pt-2 ".($this->application->isAccepted()?"text-green-500":"text-red-500"))
                    ->setText(($this->application->isAccepted()?"PŘIJAT":"NEPŘIJAT"))
            )
            ;
    }


}
