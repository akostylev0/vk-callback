<?php
declare (strict_types = 1);

namespace VkCallback;

use Closure;
use Generator;
use Icicle\Http\Message\BasicResponse;
use Icicle\Http\Message\Request;
use Icicle\Http\Message\Response;
use Icicle\Http\Server\RequestHandler;
use Icicle\Http\Server\Server;
use Icicle\Coroutine;
use Icicle\Socket\Socket;
use Icicle\Awaitable;

class CallbackServer implements RequestHandler
{
    /**
     * @var Closure[]
     */
    private $listeners;


    /**
     * @var Closure
     */
    private $confirmClosure;

    public function __construct()
    {
        $this->listeners = [];
    }

    public function confirm(string $token)
    {
        $this->confirmWithClosure(function () use ($token) : string {
            return $token;
        });
    }

    public function confirmWithClosure(Closure $closure)
    {
        $this->confirmClosure = $closure;
    }

    public function on(string $event, Closure $closure)
    {
        if (!isset($this->listeners[$event])) {
            $this->listeners[$event] = [];
        }

        $this->listeners[$event][] = Coroutine\wrap($closure);
    }

    public function run(int $port, string $address = '*')
    {
        $server = new Server($this);

        $server->listen($port, $address);

        \Icicle\Loop\run();
    }

    public function onRequest(Request $request, Socket $socket)
    {
        $data = '';
        $body = $request->getBody();
        while ($body->isReadable()) {
            $data .= yield $body->read();
        }

        $requestData = json_decode($data, true);

        if (isset($requestData['type'])) {
            $type = $requestData['type'];

            if ($type === 'confirmation') {
                $confirmToken = yield from $this->confirmToken($requestData['group_id']);

                return yield from $this->ok($confirmToken);
            }

            if (!empty($this->listeners[$type])) {
                /** @var Coroutine\Coroutine[] $coroutines */
                $coroutines = [];

                foreach ($this->listeners[$type] as $listener) {
                    $coroutines[] = $listener($requestData);
                }

                Awaitable\all($coroutines)->wait();
            }
        }

        return yield from $this->ok();
    }

    public function onError(int $code, Socket $socket)
    {
        return new BasicResponse($code);
    }

    private function ok(string $body = 'ok') : Generator
    {
        $response = new BasicResponse(Response::OK, [
            'Content-Type' => 'text/plain',
        ]);

        yield from $response->getBody()->end($body);

        return $response;
    }

    private function confirmToken(int $groupId) : Generator
    {
        $callback = $this->confirmClosure;

        if ($callback === null) {
            throw new \RuntimeException('Confirm closure does not set');
        }

        return yield $val = $callback($groupId);
    }
}
