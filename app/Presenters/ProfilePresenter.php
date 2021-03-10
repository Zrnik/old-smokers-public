<?php


namespace App\Presenters;


use App\Controls\Endor\Character\CharacterEditorFactory;
use App\Controls\Endor\Character\Editor;
use App\Model\Endor\Character;
use App\Model\Endor\CharacterRepository;
use JetBrains\PhpStorm\NoReturn;
use Nette\Application\AbortException;
use Nette\Application\Attributes\Persistent;
use Nette\Application\BadRequestException;
use Nette\Application\UI\InvalidLinkException;
use Nette\DI\Attributes\Inject;

/**
 * Class ProfilePresenter
 * @package App\Presenters
 * @property ProfileTemplate $template
 */
class ProfilePresenter extends BasePresenter
{
    #[Inject]
    public CharacterRepository $characterRepository;

    #[Inject]
    public CharacterEditorFactory $characterEditorFactory;

    #[Persistent]
    public ?int $characterId = null;

    /**
     * @throws AbortException
     * @throws InvalidLinkException
     */
    protected function startup(): void
    {
        // Celej presenter pouze pro přihlášené uživatele!

        if (!$this->user->isLoggedIn())
            $this->redirect("Auth:signIn", ["state" => $this->storeRequest()]);

        $this->template->characterId = $this->characterId;

        parent::startup();
    }


    public function renderDefault(): void
    {
        $this->template->characters = $this->characterRepository->getCharactersOf(
            $this->user->getId()
        );
    }


    public function createComponentCharacterEditor(): Editor
    {
        return $this->characterEditorFactory->create($this->characterId, function () {
            $this->redirect("Profile:");
        });
    }

    /**
     * @throws BadRequestException
     */
    public function renderCharacterDelete(): void
    {
        $this->template->deletedCharacter = $this->tryGetCharacter($this->characterId);
    }

    /**
     * @throws BadRequestException
     */
    public function renderCharacterEditor(): void
    {
        // jen pro zjištění existence a vlastnictví
        $this->tryGetCharacter($this->characterId, false);
    }


    /**
     * @throws AbortException
     * @throws BadRequestException
     */
    #[NoReturn]
    public function handleCharacterDelete(): void
    {
        $character = $this->tryGetCharacter($this->characterId);

        if($character === null)
            return;


        $this->characterRepository->delete($character->id);
        $this->redirect("Profile:");
    }

    /**
     * Check if the character exists, if the user
     * is owner of the character and returns it!
     *
     * @param int|null $characterId
     * @param bool $existenceRequired
     * @return ?Character
     * @throws BadRequestException
     */
    private function tryGetCharacter(?int $characterId, bool $existenceRequired = true): ?Character
    {
        $character = $this->characterRepository->getCharacter($characterId);

        if ($existenceRequired && $character === null)
            $this->error("Tato postava neexistuje!", 404);

        if ($character !== null && $character->ownerId !== $this->user->getId())
            $this->error("Tato postava není vaše!", 403);

        return $character;
    }


}
