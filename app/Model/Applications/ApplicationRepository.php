<?php


namespace App\Model\Applications;

use Brick\DateTime\LocalDateTime;
use Brick\DateTime\TimeZone;
use Nette\Database\Connection;
use Nette\Utils\JsonException;
use Zrnik\MkSQL\Updater;
use Zrnik\MkSQL\Utilities\Installable;

class ApplicationRepository extends Installable
{

    public function __construct(private Connection $connection)
    {
        parent::__construct($connection->getPdo());
    }

    function install(Updater $updater): void
    {
        $ApplicationsTable = $updater->tableCreate("applications");

        $ApplicationsTable->columnCreate("discordId", "bigint")
            ->setUnique() // Kazdy uzivatel jen jedna prihlaska!
            ->addForeignKey("discord_users.discordId", false);

        //Text přihlášky
        $ApplicationsTable->columnCreate("applicationText", "text");

        // Chce uživatel dostávat notifikace na discord od bota?
        // Je to podmíněno přítomností na serveru!
        $ApplicationsTable->columnCreate("notificationsEnabled", "tinyint")
            ->setNotNull()->setDefault(1);

        //Kdy se naposledy něco dělo?
        $ApplicationsTable->columnCreate("lastAction", "varchar(255)")
            ->setNotNull()->setDefault(LocalDateTime::of(1970, 1, 1)->jsonSerialize());

        // Začalo hlasování?
        $ApplicationsTable->columnCreate("votingStarted", "tinyint")
            ->setNotNull()->setDefault(0);

        // Kdo jak hlasoval?
        $ApplicationsTable->columnCreate("memberVotes", "json")
            ->setNotNull();

        // Kolik je potřeba hlasů?
        $ApplicationsTable->columnCreate("votesRequired", "int")
            ->setNotNull()->setDefault(0);

        // Komentáře!

        $comments = $updater->tableCreate("application_comments");

        // Kde to komentoval?
        $comments->columnCreate("application")
            ->addForeignKey("applications.id");

        //Kdo to komentoval?
        $comments->columnCreate("discordId", "bigint")
            ->addForeignKey("discord_users.discordId", false);

        //Kdy to komentoval?
        $comments->columnCreate("commentTime", "varchar(255)")
            ->setNotNull()->setDefault(LocalDateTime::of(1970, 1, 1)->jsonSerialize());

        // Co napsal?
        $comments->columnCreate("commentText", "text")
            ->setNotNull();
    }

    public function getApplicationOf(int $id): ?Application
    {
        $applicationRow = $this->connection->fetch("SELECT * FROM applications WHERE discordId = ?", $id);

        if ($applicationRow !== null) {
            try {
                return Application::fromArray(iterator_to_array($applicationRow));
            } catch (JsonException) {
                return null;
            }
        }

        return null;
    }

    public function getApplication(?int $id): ?Application
    {
        if($id === null)
            return null;

        $applicationRow = $this->connection->fetch("SELECT * FROM applications WHERE id = ?", $id);

        if ($applicationRow !== null) {
            try {
                return Application::fromArray(iterator_to_array($applicationRow));
            } catch (JsonException) {
                return null;
            }
        }

        return null;
    }

    /**
     * Všechny přihlášky!
     *
     * @return Application[]
     * @throws JsonException
     */
    public function getList(): array
    {
        $applications = [];

        $applicationRows = $this->connection->fetchAll(
            "SELECT * FROM applications ORDER BY lastAction DESC"
        );

        foreach($applicationRows as $applicationRow)
        {
            $applications[] = Application::fromArray(iterator_to_array($applicationRow));
        }

        return $applications;
    }


    /**
     * @param Application $application
     * @throws JsonException
     */
    public function save(Application $application): void
    {
        $application->lastAction = LocalDateTime::now(TimeZone::parse(date_default_timezone_get()));

        if ($application->id === null) {

            $insertData = $application->toArray();
            unset($insertData["id"]);

            $this->connection->query("INSERT INTO applications", $insertData);

            $application->id = intval($this->connection->getInsertId());

        } else {

            $saveData = $application->toArray();
            $saveId = $saveData["id"];
            unset($saveData["id"]);

            $this->connection->query(
                "UPDATE applications SET", $saveData,
                "WHERE id = ?", $saveId
            );

        }
    }

    public function delete(Application $application): void
    {
        $this->connection->query("DELETE FROM application_comments WHERE application = ?", $application);
        $this->connection->query("DELETE FROM applications WHERE id = ?", $application);
    }

    /**
     * @param int $applicationId
     * @return ApplicationComment[]
     */
    public function getComments(int $applicationId): array
    {
        $comments = [];

        foreach(
            $this->connection->fetchAll("SELECT * FROM application_comments WHERE application = ?", $applicationId)
            as $applicationCommentRow
        )
            $comments[] = ApplicationComment::fromArray(iterator_to_array($applicationCommentRow));

        return $comments;
    }

    /**
     * @param int $applicationId
     * @param int $writerId
     * @param string $commentText
     * @return int
     * @throws JsonException
     */
    public function addComment(int $applicationId, int $writerId, string $commentText): int
    {
        $application = $this->getApplication($applicationId);

        if($application !== null)
        {
            $this->save($application); //Posune čas!
            $this->connection->query(/** @lang */ "INSERT INTO application_comments", [
                "application" => $applicationId,
                "discordId" => $writerId,
                "commentTime" => LocalDateTime::now(TimeZone::parse(date_default_timezone_get())),
                "commentText" => $commentText
            ]);

            return intval($this->connection->getInsertId());
        }

        return 0;
    }


}












