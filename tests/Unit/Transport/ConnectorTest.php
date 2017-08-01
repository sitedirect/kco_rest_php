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

namespace Klarna\Rest\Tests\Unit\Transport;

use GuzzleHttp\Exception\RequestException;
use Klarna\Rest\Tests\Unit\TestCase;
use Klarna\Rest\Transport\Connector;
use Klarna\Rest\Transport\Exception\ConnectorException;

/**
 * Unit test cases for the connector class.
 */
class ConnectorTest extends TestCase
{
    const MERCHANT_ID = '1234';

    const SHARED_SECRET = 'MySecret';

    const BASE_URL = 'http://base-url.internal.machines';

    const PATH = '/test/url';

    /**
     * @var Connector
     */
    protected $object;

    /**
     * @var \GuzzleHttp\ClientInterface
     */
    protected $client;

    /**
     * @var \Klarna\Rest\Transport\UserAgentInterface
     */
    protected $userAgent;

    /**
     * Set up the test fixtures.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->options = [
            'opt' => 'val',
            'auth' => [self::MERCHANT_ID, self::SHARED_SECRET],
            'headers' => ['User-Agent' => 'a-user-agent']
        ];

        $this->client = $this->getMockBuilder('GuzzleHttp\ClientInterface')
            ->getMock();

        $this->userAgent = $this->getMockBuilder('Klarna\Rest\Transport\UserAgent')
            ->getMock();

        $this->userAgent->expects($this->any())
            ->method('__toString')
            ->will($this->returnValue('a-user-agent'));

        $this->object = new Connector(
            $this->client,
            self::MERCHANT_ID,
            self::SHARED_SECRET,
            $this->userAgent
        );
    }

    /**
     * Make sure that the request is created as intended.
     *
     * @return void
     */
    public function testCreateRequest()
    {
        $this->client->expects($this->any())
            ->method('request')
            ->with('method', 'uri', $this->options)
            ->will($this->returnValue($this->response));

        $response = $this->object->createRequest('uri', 'method', ['opt' => 'val']);
        $this->assertSame($this->response, $response);
    }


    /**
     * Make sure that an exception without a response is re-thrown.
     *
     * @return void
     */
    public function testCreateRequestRequestException()
    {
        $exception = new RequestException(
            'Something went terribly wrong',
            $this->request
        );

        $this->client->expects($this->once())
            ->method('request')
            ->with('method', 'uri', $this->options)
            ->will($this->throwException($exception));

        $this->setExpectedException(
            'GuzzleHttp\Exception\RequestException',
            'Something went terribly wrong'
        );

        $this->object->createRequest('uri', 'method', ['opt' => 'val']);
    }

    /**
     * Make sure that an exception without a JSON response is re-thrown.
     *
     * @return void
     */
    public function testCreateRequestConnectorExceptionNoJson()
    {
        $this->response->expects($this->once())
            ->method('getHeader')
            ->with('Content-Type')
            ->will($this->returnValue(''));

        $exception = new RequestException(
            'Something went terribly wrong',
            $this->request,
            $this->response
        );

        $this->client->expects($this->once())
            ->method('request')
            ->with('method', 'uri', $this->options)
            ->will($this->throwException($exception));

        $this->setExpectedException(
            'GuzzleHttp\Exception\RequestException',
            'Something went terribly wrong'
        );

        $this->object->createRequest('uri', 'method', ['opt' => 'val']);
    }

    /**
     * Make sure that an exception without data but with json content-type is
     * re-thrown.
     *
     * @return void
     */
    public function testCreateRequestConnectorExceptionEmptyJson()
    {
        $this->response->expects($this->once())
            ->method('getHeader')
            ->with('Content-Type')
            ->will($this->returnValue('application/json'));

        $exception = new RequestException(
            'Something went terribly wrong',
            $this->request,
            $this->response
        );

        $this->client->expects($this->once())
            ->method('request')
            ->with('method', 'uri', $this->options)
            ->will($this->throwException($exception));

        $this->setExpectedException(
            'GuzzleHttp\Exception\RequestException',
            'Something went terribly wrong'
        );

        $this->object->createRequest('uri', 'method', ['opt' => 'val']);
    }

    /**
     * Make sure that an exception without a proper JSON response is re-thrown.
     *
     * @return void
     */
    public function testCreateRequestConnectorExceptionMissingFields()
    {
        $this->response->expects($this->once())
            ->method('getHeader')
            ->with('Content-Type')
            ->will($this->returnValue('application/json'));

        $data = [];

        $this->response->expects($this->once())
            ->method('getBody')
            ->will($this->returnValue(json_encode($data)));

        $exception = new RequestException(
            'Something went terribly wrong',
            $this->request,
            $this->response
        );

        $this->client->expects($this->once())
            ->method('request')
            ->with('method', 'uri', $this->options)
            ->will($this->throwException($exception));

        $this->setExpectedException(
            'GuzzleHttp\Exception\RequestException',
            'Something went terribly wrong'
        );

        $this->object->createRequest('uri', 'method', ['opt' => 'val']);
    }

    /**
     * Make sure that an exception with a error response is wrapped properly.
     *
     * @return void
     */
    public function testCreateRequestConnectorException()
    {
        $this->response->expects($this->once())
            ->method('getHeader')
            ->with('Content-Type')
            ->will($this->returnValue('application/json'));

        $data = [
            'error_code' => 'ERROR_CODE_1',
            'error_messages' => [
                'Oh dear...',
                'Oh no...'
            ],
            'correlation_id' => 'corr_id_1'
        ];

        $this->response->expects($this->once())
            ->method('getBody')
            ->will($this->returnValue(json_encode($data)));

        $exception = new RequestException(
            'Something went terribly wrong',
            $this->request,
            $this->response
        );

        $this->client->expects($this->once())
            ->method('request')
            ->with('method', 'uri', $this->options)
            ->will($this->throwException($exception));

        $this->setExpectedException(
            'Klarna\Rest\Transport\Exception\ConnectorException',
            'ERROR_CODE_1: Oh dear..., Oh no... (#corr_id_1)'
        );

        $this->object->createRequest('uri', 'method', ['opt' => 'val']);
    }

    /**
     * Make sure that the factory method creates a connector properly.
     *
     * @return void
     */
    public function testCreate()
    {
        $connector = Connector::create(
            self::MERCHANT_ID,
            self::SHARED_SECRET,
            self::BASE_URL,
            $this->userAgent
        );

        $client = $connector->getClient();
        $this->assertInstanceOf('GuzzleHttp\ClientInterface', $client);
        
        $userAgent = $connector->getUserAgent();

        $this->assertSame($this->userAgent, $userAgent);
        $this->assertEquals('a-user-agent', strval($userAgent));
    }

    /**
     * Make sure that the factory method uses the default user agent.
     *
     * @return void
     */
    public function testCreateDefaultUserAgent()
    {
        $connector = Connector::create(
            self::MERCHANT_ID,
            self::SHARED_SECRET,
            self::BASE_URL
        );

        $userAgent = $connector->getUserAgent();
        $this->assertInstanceOf('Klarna\Rest\Transport\UserAgent', $userAgent);
        $this->assertContains('Library/Klarna.kco_rest_php', strval($userAgent));
    }

    /**
     * Make sure that the client is retrievable.
     *
     * @return void
     */
    public function testGetClient()
    {
        $client = $this->object->getClient();

        $this->assertInstanceOf('GuzzleHttp\ClientInterface', $client);
        $this->assertSame($this->client, $client);
    }
}
