<?php


namespace App\Model\Endor;


use Nette\Database\Connection;
use Zrnik\MkSQL\Updater;
use Zrnik\MkSQL\Utilities\Installable;

class CharacterRepository extends Installable
{
    public function __construct(private Connection $connection)
    {
        parent::__construct($connection->getPdo());
    }

    function install(Updater $updater): void
    {

        $characterTable = $updater->tableCreate("endor_characters");

        $characterTable->columnCreate("discordId", "bigint")
            ->addForeignKey("discord_users.discordId", false);

        $characterTable->columnCreate("charname", "varchar(30)");

        $characterTable->columnCreate("job");
        $characterTable->columnCreate("race");

        $characterTable->columnCreate("bought_level");
        $characterTable->columnCreate("exps");

        $characterTable->columnCreate("notoriety");
    }

    /**
     * @param int $discordId
     * @return Character[]
     */
    public function getCharactersOf(int $discordId): array
    {
        $characters = [];

        foreach (
            $this->connection->fetchAll("SELECT * FROM endor_characters WHERE discordId = ?", $discordId)
            as $characterRow
        )
            $characters[] = Character::fromArray(iterator_to_array($characterRow));


        return $characters;
    }

    public function save(Character $character): void
    {

        if ($character->id === null) {

            $insertData = $character->toArray();
            unset($insertData["id"]);

            $this->connection->query("INSERT INTO endor_characters", $insertData);
            $character->id = intval($this->connection->getInsertId());
        } else {
            $saveData = $character->toArray();
            $saveId = $saveData["id"];
            unset($saveData["id"]);

            $this->connection->query(
                "UPDATE endor_characters SET", $saveData,
                "WHERE id = ?", $saveId
            );
        }
    }

    public function getCharacter(?int $characterId): ?Character
    {
        if($characterId !== null)
        {
            $row = $this->connection->fetch("SELECT * FROM endor_characters WHERE id = ?", $characterId);
            if($row !== null)
            {
                return Character::fromArray(iterator_to_array($row));
            }
        }
        return null;
    }

    public function delete(?int $id): void
    {
        $this->connection->query("DELETE FROM endor_characters WHERE id = ?", $id);
    }

    /**
     * @param array<int> $userIds
     * @return Character[]
     */
    public function getCharactersOfMultiple(array $userIds): array
    {
        $result = [];

        foreach(
            $this->connection->fetchAll("SELECT * FROM endor_characters WHERE discordId IN (?)", $userIds)
            as $characterRow
        )
            $result[] = Character::fromArray(iterator_to_array($characterRow));

        return $result;
    }
}
