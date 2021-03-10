<?php


namespace App\Controls\ScreenShots;



use App\Model\Discord\Connection\Messages;
use App\Model\ScreenShots\ScreenShotRepository;
use App\Model\Security\DiscordUser;

class ScreenshotUploadFactory
{
    public function __construct(
        private DiscordUser $user,
        private ScreenShotRepository $screenShotRepository,
        private Messages $messages
    ){

    }

    public function create(): ScreenshotUploadForm
    {
        return new ScreenshotUploadForm($this->user, $this->screenShotRepository, $this->messages);
    }
}
