<?php
declare (strict_types = 1);

namespace VkCallback;

use Icicle\Http\Client\Client as HttpClient;
use Icicle\Coroutine;
use VkCallback\Exception\ApiException;

class Client
{
    const VERSION = '5.52';
    const BASE_URL = 'https://api.vk.com/method/';

    /**
     * @var string
     */
    private $token;

    public function __construct(string $token)
    {
        $this->httpClient = new HttpClient();
        $this->token = $token;
    }

    public function call(string $method, array $parameters)
    {
        $parameters = array_merge([
            'access_token' => $this->token,
            'v' => self::VERSION
        ], $parameters);

        $url = self::BASE_URL . $method . '?' . http_build_query($parameters);

        /** @var \Icicle\Http\Message\Response $response */
        $response = yield from $this->httpClient->request('GET', $url);

        $data = '';
        $stream = $response->getBody();
        while ($stream->isReadable()) {
            $data .= yield from $stream->read();
        }

        $responseData = json_decode($data, true);

        if (isset($responseData['error'])) {
            throw ApiException::fromResponse($responseData);
        }

        return $responseData;
    }
}
