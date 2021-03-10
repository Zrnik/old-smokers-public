<?php

namespace App\Model\Discord\Connection;

use App\Model\Discord\User;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Nette\Database\Connection;
use Nette\Http\UrlImmutable;
use Nette\Http\UrlScript;
use Nette\Utils\Json;
use Nette\Utils\JsonException;

class OAuth2
{
    const RequiredDiscordScopes = 'identify'; // Jeste muze byt: 'email' a 'guilds', ale asi netřeba.

    private Client $httpClient;

    public function __construct(
        private Connection $connection
    )
    {
        $this->httpClient = new Client();
    }

    private int $clientId = -1;
    private string $clientSecret = '';

    public function setClientSecret(string $clientSecret): void
    {
        $this->clientSecret = $clientSecret;
    }


    public function setClientId(int $clientId): void
    {
        $this->clientId = $clientId;
    }

    /**
     * @param string $callbackUrl
     * @param array<string, string> $additionalParameters
     * @return UrlImmutable
     */
    public function getSignInUrl(string $callbackUrl, array $additionalParameters): UrlImmutable
    {
        $realRedirectUri =  (new UrlScript($callbackUrl))->withQuery([]);

        $url = new UrlScript("https://discord.com/api/oauth2/authorize");
        return $url->withQuery(array_merge([
            "client_id" => $this->clientId,
            "redirect_uri" =>$realRedirectUri->getAbsoluteUrl(),
            "response_type" => "code",
            "scope" => self::RequiredDiscordScopes
        ], $additionalParameters));
    }

    /**
     * @param string $code
     * @param UrlScript $callbackUrl
     * @return mixed
     * @throws GuzzleException
     * @throws JsonException
     */
    public function getCodeInfo(string $code, UrlScript $callbackUrl)
    {
        $realRedirectUri =  (new UrlScript($callbackUrl))->withQuery([]);

        return Json::decode(
            $this->httpClient->post(
                "https://discord.com/api/oauth2/token",
                [
                    "form_params" => [
                        "client_id" => $this->clientId,
                        "client_secret" => $this->clientSecret,
                        'grant_type' => 'authorization_code',
                        "code" => $code,
                        "redirect_uri" => $realRedirectUri->getAbsoluteUrl(),
                        "scope" => self::RequiredDiscordScopes
                    ]
                ]
            )->getBody()->__toString(),
            Json::FORCE_ARRAY
        );
    }

    /**
     * @param string $accessToken
     * @return User
     * @throws GuzzleException
     * @throws JsonException
     */
    public function getMe(string $accessToken): User
    {
        return new User(
            Json::decode(
                $this->httpClient->get(
                    "https://discord.com/api/users/@me",
                    [
                        "headers" => [
                            "Authorization" => "Bearer " . $accessToken
                        ]

                    ]
                )->getBody()->__toString(),
                Json::FORCE_ARRAY
            )
        );
    }

}
