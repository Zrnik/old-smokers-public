<?php


namespace App\Controls\ScreenShots;

use App\Model\ScreenShots\ScreenShotRepository;
use App\Model\Security\DiscordUser;

class ScreenshotCommentFormFactory
{
    public function __construct(
        private DiscordUser $user,
        private ScreenShotRepository $screenShotRepository
    )
    {

    }

    public function create(int $screenshotId): ScreenshotCommentForm
    {
        return new ScreenshotCommentForm($this->user, $this->screenShotRepository, $screenshotId);
    }
}
