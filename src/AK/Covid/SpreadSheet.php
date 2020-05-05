<?php

namespace AK\Covid;

use Symfony\Component\HttpFoundation\Request;
use Google_Client;
use Google_Service_Sheets;
use Google_Service_Sheets_BatchUpdateSpreadsheetRequest;
use Google_Service_Sheets_Request;
use Exception;

class SpreadSheet {
    const HEADERS_RANGE = 'A1:I1';
    const DATA_RANGE = 'A%d:I%d';

    protected $service;
    protected $query;

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
        'address' => 'Vilnius,  Lithuania',
        'area' => '',
    ];

    // SpredSheetID={ssid}&ActiveSheetName={sheetName}&range={range}&rangeStart={rangeStart}&rangeEnd={rangeEnd}&resultColumn={resultColumn}&callBack={callback}
    public function __construct(Application $app, Request $req)
    {
        $this->query = $req->query->all();
        $client = self::getClient($app['credentials_file'], $app['token'], $app['token_file']);
        $this->service = new Google_Service_Sheets($client);
        self::$defaults['date'] = date('Y-m-d');
    }

    public function getData()
    {
        $spreadsheetId = $this->query['SpreadSheetId'];

        $headersRange =  sprintf('%s!%s', $this->query['ActiveSheetName'], self::HEADERS_RANGE);
        $dataRange = sprintf('%s!%s', $this->query['ActiveSheetName'], sprintf(self::DATA_RANGE, $this->query['rangeStart'], $this->query['rangeEnd']));
        $response = $this->service->spreadsheets_values->batchGet($spreadsheetId, ['ranges' => [$headersRange, $dataRange]]);

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

    public function updateSheet($ids, $resultColumn)
    {
        $values = [];
        foreach ($ids as $id) {
            $values[] =[$id];
        }

        $spreadsheetId = $this->query['SpreadSheetId'];
        $sheetId = $this->query['ActiveSheetName'];
        $startRow = $this->query['rangeStart'];
        $columnLetter = self::stringFromColumnIndex($resultColumn);

        $range = sprintf('%s!%s%d', $spreadsheetId, $columnLetter, $startRow);

        $request = new Google_Service_Sheets_ValueRange();
        $request->setMajorDimension('ROWS');
        $request->setValues($values);

        $service->spreadsheets_values->update($spreadsheetId, $range, $request, ['valueInputOption' => 'USER_ENTERED']);

    }

    private static function getClient($credentialsFile, $token, $tokenFile)
    {
        $client = new Google_Client();
        $client->setApplicationName('Maneki');
        $client->setScopes(Google_Service_Sheets::SPREADSHEETS);
        $client->setAuthConfig($credentialsFile);
        $client->setAccessType('offline');
        $client->setAccessToken($token);

        if ($client->isAccessTokenExpired()) {
            if ($client->getRefreshToken()) {
                $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            }
            file_put_contents($tokenFile, json_encode($client->getAccessToken()));
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

    public static function stringFromColumnIndex($columnIndex)
    {
        $indexValue = $columnIndex;
        $base26 = null;
        do {
            $characterValue = ($indexValue % 26) ?: 26;
            $indexValue = ($indexValue - $characterValue) / 26;
            $base26 = chr($characterValue + 64) . ($base26 ?: '');
        } while ($indexValue > 0);

        return $base26;
    }
}
