<?php
/**
 * Copyright 2014 Klarna AB
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * File containing tests for the Connector class.
 */

namespace Klarna\Rest\Tests\Component\Transport;

use GuzzleHttp\Psr7;
use GuzzleHttp\Psr7\Response;
use Klarna\Rest\Transport\Connector;
use Klarna\Rest\Tests\Component\TestCase;

/**
 * Component test cases for the connector class.
 */
class ConnectorTest extends TestCase
{
    /**
     * Make sure that an API error response throws a connector exception.
     *
     * @return void
     */
    public function testRequestError()
    {
        $json = <<<JSON
{
    "error_code": "ERR_1",
    "error_messages": [
        "msg1",
        "msg2"
    ],
    "correlation_id": "cid_1"
}
JSON;
        $response = new Response(
            500,
            ['Content-Type' => 'application/json'],
            Psr7\stream_for($json)
        );
        $this->mock->append($response);

        $this->setExpectedException(
            'Klarna\Rest\Transport\Exception\ConnectorException',
            'ERR_1: msg1, msg2 (#cid_1)'
        );

        $this->connector->request('http://somewhere/path', 'POST');
    }

    /**
     * Make sure that an error response throws an exception.
     *
     * @return void
     */
    public function testRequestGuzzleError()
    {
        $response = new Response(404);
        $this->mock->append($response);

        $this->setExpectedException('GuzzleHttp\Exception\ClientException');

        $this->connector->request('http://somewhere/path', 'POST');
    }

    /**
     * Make sure that the factory method creates a connector as expected.
     *
     * @return void
     */
    public function testCreate()
    {
        $userAgent = $this->getMockBuilder('Klarna\Rest\Transport\UserAgent')
            ->getMock();

        $connector = Connector::create(
            self::MERCHANT_ID,
            self::SHARED_SECRET,
            self::BASE_URL,
            $userAgent
        );

        $this->assertSame($userAgent, $connector->getUserAgent());
    }
}
