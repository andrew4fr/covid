<?php

require_once __DIR__.'/../vendor/autoload.php';

use AK\Covid\Application;
use AK\Covid\SpreadSheet;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

$confDir = __DIR__ . '/../config/';
$app = new Application([
    'credentials_file' => $confDir . 'credentials.json',
    'token_file' => $confDir . 'token.json',
]);

$app->get('/', function(Application $app, Request $req) {
    $ss = new SpreadSheet($app, $req);
    try {
        $data = $ss->getData();
        $response = [
            'status' => ['code' => 200, 'description' => 'ok'],
            'answer' => $data
        ];
    } catch (Exception $e) {
        $response = [
            'status' => ['code' => 400, 'description' => 'Bad request'],
            'error' => $e->getMessage()
        ];
    }

    return $app->json($response, $response['status']['code']);
});

$app->run();
