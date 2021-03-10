<?php


namespace App\Model\Discord\Connection;


use App\Model\Discord\Exceptions\MessageException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Nette\Utils\Json;
use Nette\Utils\JsonException;

class Messages
{

    private Client $httpClient;

    private int $notificationChannelId;

    private string $botToken;

    public function __construct()
    {
        $this->httpClient = new Client();
    }

    public function setBotToken(string $botToken): void
    {
        $this->botToken = $botToken;
    }

    public function setNotificationChannelId(int $notificationChannelId): void
    {
        $this->notificationChannelId = $notificationChannelId;
    }

    /**
     * @param string $text
     * @param int $channelId
     * @throws MessageException
     */
    private function channelMessage(string $text, int $channelId): void
    {
        try {
            $this->httpClient->post(
                "https://discord.com/api/channels/" . $channelId . "/messages",
                [
                    "headers" => [
                        "Content-Type" => "application/json",
                        "Authorization" => "Bot " . $this->botToken
                    ],
                    RequestOptions::JSON => [
                        "content" => $text,
                        "tts" => false,
                    ]
                ]
            );
        } catch (GuzzleException $e) {
            throw new MessageException($e->getMessage(), 0, $e);
        }
    }

    /**
     * @param string $text
     * @throws MessageException
     */
    public function sendNotification(string $text): void
    {
        $this->channelMessage($text, $this->notificationChannelId);
    }

    /**
     * @param int $recipientId
     * @param string $text
     * @throws MessageException
     */
    public function sendTo(int $recipientId, string $text): void
    {

        try {
            $dmChannel = Json::decode(
                $this->httpClient->post(
                    "https://discord.com/api/users/@me/channels",
                    [
                        "headers" => [
                            "Content-Type" => "application/json",
                            "Authorization" => "Bot " . $this->botToken
                        ],
                        RequestOptions::JSON => [
                            "recipient_id" => $recipientId,
                        ]
                    ]
                )->getBody()->__toString()
            );
        } catch (GuzzleException | JsonException $e) {
            throw new MessageException($e->getMessage(), 0, $e);
        }

        $this->channelMessage($text, $dmChannel->id);
    }

    public function successIndicator(bool $success, string $text): string
    {
        if($success)
        {
            return "```yaml\n".$text."\n```";
        }
        else
        {
            return "```diff\n- ".$text."\n```";
        }
    }


}
