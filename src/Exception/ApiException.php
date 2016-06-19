<?php
declare (strict_types = 1);

namespace VkCallback\Exception;

class ApiException extends \Exception
{
    /**
     * @var array
     */
    private $parameters;

    public static function fromResponse(array $response) {
        $parameters = [];

        foreach($response['error']['request_params'] as $param) {
            $parameters[$param['key']] = $param['value'];
        }

        return new self($response['error']['error_msg'], $response['error']['error_code'], $parameters);
    }

    public function __construct(string $message, int $code, array $parameters)
    {
        parent::__construct($message, $code);

        $this->parameters = $parameters;
    }

    public function getParameters() : array
    {
        return $this->parameters;
    }
}
