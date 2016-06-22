VkCallback
====

Async server and client for VK Callback API built on top of Icicle.

TLDR
===

```php
<?php
declare (strict_types = 1);

use VkCallback\CallbackServer;
use VkCallback\Client;

require __DIR__ . '/../vendor/autoload.php';

$server = new CallbackServer();
$client = new Client('your token');

$server->confirm('your confirm callback token');
$server->on('wall_reply_new', function (array $data) use ($client) {
    $response = yield from $client->call('wall.deleteComment', [
        'owner_id' => -$data['group_id'],
        'comment_id' => $data['object']['id']
    ]);

    var_dump($response);
});

$server->run(7070);
```
