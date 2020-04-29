<?php

namespace Tests\AK\Covid;

use PHPUnit\Framework\TestCase;
use AK\Covid\{
    SpreadSheet,
    Application
};
use Symfony\Component\HttpFoundation\Request;

class SpreadSheetTest extends TestCase
{
    public function testSS()
    {
        $app = $this->getMockBuilder('AK\Covid\Application')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $req = new Request();
        $ss = new SpreadSheet($app, $req);
        $this->assertInstanceOf(SpreadSheet::class, $ss);
    }

    public function testMapHeaders()
    {
        $app = $this->getMockBuilder('AK\Covid\SpreadSheet')
            ->disableOriginalConstructor()
            ->setMethods(['fakeMethod'])
            ->getMock()
        ;

        $result = ['area' => 0, 'address' => 1, 'request_author' => 2, 'sender_name' => 5];
        $headers = ['AREA', 'CITY/ ADRESS', 'NAME SIRNAME', 'CONTACT', 'PROBLEM', 'VOLUNTEER, COMMENTS', 'VOLUNTEER CONTACT', 'STATUS OF THE PROBLEM', 'Comments about the process'];
        $mappedHeaders = $app->mapHeaders($headers);

        $this->assertEquals($result, $mappedHeaders);
    }

    public function testMapData()
    {
        $app = $this->getMockBuilder('AK\Covid\SpreadSheet')
            ->disableOriginalConstructor()
            ->setMethods(['fakeMethod'])
            ->getMock()
        ;

        $data = [
            ['London', 'Square', 'Jack', '812', 'Problem', 'Peter', '456', 'New', 'Comment'],
            ['London', '', 'Jack', '812', 'Problem', 'Peter', '456', 'New', 'Comment'],
            ['London', '', 'Jack'],
        ];
        $result = [
            ['message' => '', 'category' => 'default_category', 'ttl' => 10, 'date' => date('Y-m-d'), 'request_author' => 'Jack', 'sender_name' => 'Peter', 'address' => 'Square', 'area' => 'London'],
            ['message' => '', 'category' => 'default_category', 'ttl' => 10, 'date' => date('Y-m-d'), 'request_author' => 'Jack', 'sender_name' => 'Peter', 'address' => '', 'area' => 'London'],
            ['message' => '', 'category' => 'default_category', 'ttl' => 10, 'date' => date('Y-m-d'), 'request_author' => 'Jack', 'sender_name' => '', 'address' => '', 'area' => 'London'],
        ];
        $mappedHeaders = ['area' => 0, 'address' => 1, 'request_author' => 2, 'sender_name' => 5];
        $mappedData = $app->mapData($mappedHeaders, $data);

        $this->assertEquals($result, $mappedData);
    }

}
