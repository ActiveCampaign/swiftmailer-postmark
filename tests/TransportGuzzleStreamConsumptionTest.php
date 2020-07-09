<?php

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Postmark\ThrowExceptionOnFailurePlugin;
use Postmark\Transport;

require_once __DIR__ . '/../vendor/autoload.php';

class TransportGuzzleStreamConsumptionTest extends TestCase
{

    public function testSendWithoutMiddleware()
    {
        $message = new Swift_Message();

        $transport = new TransportGuzzleStreamConsumptionTestPostmarkTransportStubNoMiddleware([
            new Response(422, [], 'Some error from server'),
        ]);
        $transport->registerPlugin(new ThrowExceptionOnFailurePlugin());

        $exception = null;
        try {
            $transport->send($message);
        } catch (Swift_TransportException $exception) {
            // Deliberately empty
        }

        $this->assertNotNull($exception);
        $this->assertInstanceOf(Swift_TransportException::class, $exception);
        $this->assertSame('Some error from server', $exception->getMessage());
    }

    public function testSendWithMiddleware()
    {
        $message = new Swift_Message();

        $transport = new TransportGuzzleStreamConsumptionTestPostmarkTransportStubWithConsumingMiddleware([
            new Response(422, [], 'Some error from server'),
        ]);
        $transport->registerPlugin(new ThrowExceptionOnFailurePlugin());

        $exception = null;
        try {
            $transport->send($message);
        } catch (Swift_TransportException $exception) {
            // Deliberately empty
        }

        $this->assertNotNull($exception);
        $this->assertInstanceOf(Swift_TransportException::class, $exception);

        // This would fail if \Postmark\Transport::send would use
        // getBody->getContents() instead of getBody->__toString()
        $this->assertSame('Some error from server', $exception->getMessage());
    }
}

class TransportGuzzleStreamConsumptionTestPostmarkTransportStubNoMiddleware extends Transport
{
    protected $client;

    public function __construct(array $responses = [])
    {
        parent::__construct('TESTING_SERVER');

        $this->client = $this->mockGuzzle($responses);
    }

    protected function getHttpClient()
    {
        return $this->client;
    }

    private function mockGuzzle(array $responses)
    {
        $stack = HandlerStack::create(new MockHandler($responses));

        return new Client(['handler' => $stack]);
    }
}

class TransportGuzzleStreamConsumptionTestPostmarkTransportStubWithConsumingMiddleware extends Transport
{
    protected $client;

    public function __construct(array $responses = [])
    {
        parent::__construct('TESTING_SERVER');

        $this->client = $this->mockGuzzle($responses);
    }

    protected function getHttpClient()
    {
        return $this->client;
    }

    private function mockGuzzle(array $responses)
    {
        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(
            function (callable $handler) {
                return function ($request, array $options) use ($handler) {
                    return $handler($request, $options)->then(
                        function (Response $response) {
                            // Pretend to do something with $response, like logging
                            $response->getBody()->__toString();

                            return $response;
                        }
                    );
                };
            }
        );

        return new Client(['handler' => $stack]);
    }
}

