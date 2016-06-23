<?php
declare (strict_types = 1);

use VkCallback\CallbackServer;
use VkCallback\Client;

require __DIR__ . '/../vendor/autoload.php';

$server = new CallbackServer();
$client = new Client('your group token');

$server->confirm('your confirm callback token');
$server->on('message_new', function (array $data) use ($client) {
    yield from $client->call('messages.send', [
        'user_id' => $data['object']['user_id'],
        'message' => $data['object']['body']
    ]);
});

$server->run(7070);
