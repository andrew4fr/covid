<?php

namespace AK\Covid;

use Symfony\Component\HttpFoundation\Request;
use Google_Client;
use Google_Service_Sheets;

class SpreadSheet {
    protected $app;
    protected $req;

    public function __construct(Application $app, Request $req)
    {
        $this->app = $app;
        $this->req = $req;
    }

    public function getData()
    {
        // SpredSheetID={ssid}&ActiveSheetName={sheetName}&range={range}&rangeStart={rangeStart}&rangeEnd={rangeEnd}&resultColumn={resultColumn}&callBack={callback}
        $query = $this->req->query->all();
        var_dump($query);
        $client = $this->getClient();
        $service = new Google_Service_Sheets($client);
        $spreadsheetId = $query['SpreadSheetId'];

        $headersRange =  sprintf('%s!A1:I1', $query['ActiveSheetName']);
        $dataRange = sprintf('%s!A%d:I%d', $query['ActiveSheetName'], $query['rangeStart'], $query['rangeEnd']);
        $response = $service->spreadsheets_values->batchGet($spreadsheetId, ['ranges' => [$headersRange, $dataRange]]);
        $rangesValues = $response->getValueRanges();

        return $rangesValues;
    }

    function getClient()
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
}
