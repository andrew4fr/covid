<?php

namespace AK\Covid;

use Symfony\Component\HttpFoundation\Request;
use Exception;

class ScreamAPI {
    const TOKEN_PARAM = 'ScreamAccessToken';
    const SCREAM_API_URL = 'https://api.iscream.xyz/api';

    protected $token;

    public function __construct(Request $req)
    {
        $token = $req->query->get(self::TOKEN_PARAM);
        if (!$token) {
            throw new Exception('Scream API access token not found');
        }

        $this->token = $token;
    }

    public function send($data)
    {

        $headers = [
            'Content-type: application/json; charset=utf8',
        ];

        foreach ($data as $d) {
            $event = [
                'query' => ['accessToken' => $this->token],
                'json' => [
                    'address' => trim(sprintf('%s %s', $d['area'], $d['address'])),
                    'request_author' => $d['request_author'],
                    'sender_name' => $d['sender_name'],
                ],
                'messsage' => $d['message'],
                'ttl' => $d['ttl'],
                'category' => $d['category'],
                'status' => 'new',
                'date' => $d['date']
            ];

            $options = [
                'http' => [
                    'ignore_errors' => true,
                    'method' => 'POST',
                    'header' => implode("\r\n", $headers),
                    'content' => json_encode($event)
                ]
            ];
            var_dump($options);
            $stream = stream_context_create($options);

            $answer = @file_get_contents(sprintf('%s/%s', self::SCREAM_API_URL, 'screams/create'), false, $stream);
            var_dump($answer);
            $code = self::getCode($http_response_header);
            if (!in_array($code, [200, 201])) {
                throw Exception(sprintf('Scream API error: %s', $answer));
            }
            sleep(1);
        }
    }

    private static function getCode($headers) {
        $code = 0;
        foreach ($headers as $header) {
            if (preg_match( "@HTTP/[\d\.]+\s+([\d]+)@", $header, $matches)) {
                $code = (int)$matches[1];
                break;
            }
        }

        return $code;
    }
}
