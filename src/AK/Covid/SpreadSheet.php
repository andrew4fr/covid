<?php

namespace AK\Covid;

use Symfony\Component\HttpFoundation\Request;
use Google_Client;
use Google_Service_Sheets;
use Exception;

class SpreadSheet {
    const HEADERS_RANGE = 'A1:I1';
    const DATA_RANGE = 'A%d:I%d';

    protected $app;
    protected $req;

    protected static $fields = [
        'message' => ['message', 'сообщение'],
        'category' => ['category', 'категория'],
        'ttl' => ['ttl', 'time to life'],
        'date' => ['date', 'дата'],
        'request_author' => ['name sirname'],
        'sender_name' => ['volunteer, comments'],
        'address' => ['city/ adress'],
        'area' => ['area'],
    ];

    protected static $defaults = [
        'message' => 'default message',
        'category' => 'default_category',
        'ttl' => '10',
        'request_author' => '',
        'sender_name' => '',
        'address' => '',
        'area' => '',
    ];

    public function __construct(Application $app, Request $req)
    {
        $this->app = $app;
        $this->req = $req;
        self::$defaults['date'] = date('Y-m-d');
    }

    public function getData()
    {
        // SpredSheetID={ssid}&ActiveSheetName={sheetName}&range={range}&rangeStart={rangeStart}&rangeEnd={rangeEnd}&resultColumn={resultColumn}&callBack={callback}
        $query = $this->req->query->all();
        $client = $this->getClient();
        $service = new Google_Service_Sheets($client);
        $spreadsheetId = $query['SpreadSheetId'];

        $headersRange =  sprintf('%s!%s', $query['ActiveSheetName'], self::HEADERS_RANGE);
        $dataRange = sprintf('%s!%s', $query['ActiveSheetName'], sprintf(self::DATA_RANGE, $query['rangeStart'], $query['rangeEnd']));
        $response = $service->spreadsheets_values->batchGet($spreadsheetId, ['ranges' => [$headersRange, $dataRange]]);

        $valueRanges = $response->getValueRanges();

        $headers = self::getValues($valueRanges, $headersRange)[0];
        if (count($headers) == 0) {
            throw new Exception("Headers not found");
        }
        $data = self::getValues($valueRanges, $dataRange);
        if (count($data) == 0) {
            throw new Exception("Data not found");
        }

        $mappedHeaders = $this->mapHeaders($headers);
        $mappedData = $this->mapData($mappedHeaders, $data);
        return $mappedData;
    }

    private function getClient()
    {
        $client = new Google_Client();
        $client->setApplicationName('Maneki');
        $client->setScopes(Google_Service_Sheets::SPREADSHEETS_READONLY);
        $client->setAuthConfig($this->app['credentials_file']);
        $client->setAccessType('offline');
        $client->setAccessToken($this->app['token']);

        if ($client->isAccessTokenExpired()) {
            if ($client->getRefreshToken()) {
                $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            }
            file_put_contents($this->app['token_file'], json_encode($client->getAccessToken()));
        }
        return $client;
    }

    private static function getValues(&$valueRanges, $range)
    {
        foreach ($valueRanges as $r) {
            if ($r->getRange() == $range) {
                return $r->getValues();
            }
        }

        return [[]];
    }

    public function mapHeaders($headers)
    {
        $result = [];
        foreach ($headers as $idx => $header) {
            $h = mb_strtolower(trim($header), 'UTF-8');
            foreach (self::$fields as $key => $values)  {
                if (in_array($h, $values)) {
                    $result[$key] = $idx;
                }
            }
        }

        return $result;
    }

    public function mapData($headers, $data) {
        $results = [];
        foreach ($data as $row) {
            $result = [];
            foreach (array_keys(self::$fields) as $field) {
                if (isset($headers[$field])) {
                    $v = $row[$headers[$field]] ?? self::$defaults[$field];
                    if (!$v) {
                        $v = self::$defaults[$field];
                    }
                } else {
                    $v = self::$defaults[$field];
                }
                $result[$field] = $v;
            }
            $results[] = $result;
        }

        return $results;
    }
}
