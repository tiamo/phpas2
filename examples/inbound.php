<?php

require_once "bootstrap.php";

use GuzzleHttp\Psr7\ServerRequest;

$request = ServerRequest::fromGlobals();
$messageId = trim($request->getHeaderLine('message-id'), '<>');

//if (!$messageId) {
//    throw new \RuntimeException('Message-ID required.');
//}

$headers = '';
foreach($request->getHeaders() as $name => $header) {
    $headers .= $name .= ': ' . implode(',', $header) . "\r\n";
}
$headers .= "\r\n";

file_put_contents('data/inbound/' . $messageId . '.raw', $headers . file_get_contents('php://input'));

$server = new \AS2\Server($manager, $storage);

//$message = file_get_contents('data/inbound/mendelson_opensource_AS2-1514906682749-1@mycompanyAS2_phpas2.raw');
//$payload = \AS2\Utils::parseMessage($message);
////$payload['body'] = \AS2\Utils::encodeBase64($payload['body']);
//
//$serverRequest = new ServerRequest(
//    'POST',
//    'http:://localhost',
//    $payload['headers'],
//    $payload['body'],
//    '1.1',
//    [
//        'REMOTE_ADDR' => '127.0.0.1'
//    ]
//);
//$response = $server->execute($serverRequest);

$response = $server->execute();

foreach ($response->getHeaders() as $name => $values) {
    foreach ($values as $value) {
        header(sprintf('%s: %s', $name, $value), false);
    }
}

echo $response->getBody()->getContents();