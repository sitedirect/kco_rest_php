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
 * File containing the TestCase class.
 */

namespace Klarna\Rest\Tests\Component;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Middleware;
use GuzzleHttp\HandlerStack;
use Klarna\Rest\Transport\Connector;
use Psr\Http\Message\RequestInterface;

/**
 * Base component test case class.
 */
abstract class TestCase extends \PHPUnit_Framework_TestCase
{
    const MERCHANT_ID = '1234';

    const SHARED_SECRET = 'MySecret';

    const BASE_URL = 'http://base-url.internal.machines';

    const PATH = '/test/url';

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var Connector
     */
    protected $connector;

    /**
     * @var Mock
     */
    protected $mock;

    /**
     * @var History
     */
    protected $history;

    /**
     * Sets up the test fixtures.
     */
    protected function setUp()
    {
        $this->mock = new MockHandler();
        $this->history = [];

        $handler = HandlerStack::create($this->mock);
        $handler->push(Middleware::history($this->history));

        $this->client = new Client(['handler' => $handler]);

        $this->connector = new Connector(
            $this->client,
            self::MERCHANT_ID,
            self::SHARED_SECRET
        );
    }
}
