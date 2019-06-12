<?php

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../vendor/autoload.php';

class MailPostmarkTransportTest extends TestCase {

	public function testSend() {
		$message = new Swift_Message();
		$message->setFrom('johnny5@example.com', 'Johnny #5');
		$message->setSubject('Is alive!');
		$message->addTo('you@example.com', 'A. Friend');
		$message->addTo('you+two@example.com');
		$message->addCc('another+1@example.com');
		$message->addCc('another+2@example.com', 'Extra 2');
		$message->addBcc('another+3@example.com');
		$message->addBcc('another+4@example.com', 'Extra 4');
		$message->addPart('<q>Help me Rhonda</q>', 'text/html');
		$message->addPart('Doo-wah-ditty.', 'text/plain');

		$attachment = new Swift_Attachment('This is the plain text attachment.', 'hello.txt', 'text/plain');
		$attachment2 = new Swift_Attachment('This is the plain text attachment.', 'hello.txt', 'text/plain');
		$attachment2->setDisposition('inline');

		$message->attach($attachment);
		$message->attach($attachment2);
		$message->setPriority(1);

		$headers = $message->getHeaders();
		$headers->addTextHeader('X-PM-Tag', 'movie-quotes');

		$transport = new PostmarkTransportStub([new Response(200)]);

		$recipientCount = $transport->send($message);

		$this->assertEquals(6, $recipientCount);
		$transaction = $transport->getHistory()[0];
		$this->assertExpectedMessageRequest($message, $transaction['request']);
	}

    protected function assertExpectedMessageRequest($message, $request)
    {
        $attachments = $this->getAttachments($message);

        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('TESTING_SERVER', $request->getHeaderLine('X-Postmark-Server-Token'));
        $this->assertEquals('swiftmailer-postmark (PHP Version: '.phpversion().', OS: '.PHP_OS.')', $request->getHeaderLine('User-Agent'));
        $this->assertEquals('application/json', $request->getHeaderLine('Content-Type'));
        $this->assertEquals([
            'From' => '"Johnny #5" <johnny5@example.com>',
            'To' => '"A. Friend" <you@example.com>,you+two@example.com',
            'Cc' => 'another+1@example.com,"Extra 2" <another+2@example.com>',
            'Bcc' => 'another+3@example.com,"Extra 4" <another+4@example.com>',
            'Subject' => 'Is alive!',
            'Tag' => 'movie-quotes',
            'TextBody' => 'Doo-wah-ditty.',
            'HtmlBody' => '<q>Help me Rhonda</q>',
            'Headers' => [
                ['Name' => 'Message-ID', 'Value' => '<' . $message->getHeaders()->get('Message-ID')->getId() . '>'],
                ['Name' => 'X-PM-KeepID', 'Value' => 'true'],
                ['Name' => 'X-Priority', 'Value' => '1 (Highest)'],
            ],
            'Attachments' => [
                [
                    'ContentType' => 'text/plain',
                    'Content' => 'VGhpcyBpcyB0aGUgcGxhaW4gdGV4dCBhdHRhY2htZW50Lg==',
                    'Name' => 'hello.txt',
                ],
                [
                    'ContentType' => 'text/plain',
                    'Content' => 'VGhpcyBpcyB0aGUgcGxhaW4gdGV4dCBhdHRhY2htZW50Lg==',
                    'Name' => 'hello.txt',
                    'ContentID' => 'cid:'.$attachments[1]->getId()
                ],
            ]
        ], json_decode($request->getBody()->getContents(), true));
	}

	public function testCanSendEmailsWithCCs()
    {
        $message = new Swift_Message();
        $message->setFrom('johnny5@example.com', 'Johnny #5');
        $message->addTo('you@example.com', 'A. Friend');
        $message->addCc('another+1@example.com');

        $transport = new PostmarkTransportStub([new Response(200)]);

        $recipientCount = $transport->send($message);

        $this->assertEquals(2, $recipientCount);
    }

    protected function getAttachments($message)
    {
        return array_values(array_filter($message->getChildren(), function ($child) {
            return $child instanceof Swift_Attachment;
        }));
    }

    public function testEvents()
    {
        $message = new Swift_Message();
        $message->setFrom('johnny5@example.com', 'Johnny #5');
        $message->setSubject('Some Subject');
        $message->addTo('you@example.com', 'A. Friend');
        $transport = new PostmarkTransportStub([new Response(200)]);
     
        $redirectAddress = 'test@test.com';

        $transport->registerPlugin(new Swift_Plugins_RedirectingPlugin('test@test.com'));
        
        $o = PHP_OS;
        $v = phpversion();

        $transport->send($message);

        $request = $transport->getHistory()[0]['request'];
        $body = json_decode($request->getBody()->getContents(), true);
        $this->assertEquals($redirectAddress, $body['To']);
    }

    public function testToAndCcCanBeNullableEvents()
    {
        $message = new Swift_Message();
        $message->setFrom('johnny5@example.com', 'Johnny #5');
        $message->setSubject('Some Subject');
        $message->addBcc('you@example.com', 'A. Friend');
        $message->addBcc('other@example.com', 'B. Friend');
        $transport = new PostmarkTransportStub([new Response(200)]);

        $o = PHP_OS;
        $v = phpversion();

        $transport->send($message);

        $request = $transport->getHistory()[0]['request'];
        $body = json_decode($request->getBody()->getContents(), true);
        $this->assertEquals('"A. Friend" <you@example.com>,"B. Friend" <other@example.com>', $body['Bcc']);
    }

    public function testServerTokenReturnedFromPublicMethod()
    {
        $transport = new PostmarkTransportStub();
        $this->assertEquals($transport->getServerToken(), 'TESTING_SERVER');
    }

    public function testFailedResponse()
    {
        $message = new Swift_Message();

        $transport = new PostmarkTransportStub([new Response(401)]);
        $transport->registerPlugin(new \Postmark\ThrowExceptionOnFailurePlugin());

        $this->expectException(\Swift_TransportException::class);
        $transport->send($message);
    }
}


class PostmarkTransportStub extends Postmark\Transport {
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

    public function getHistory()
    {
        return $this->client->transactionHistory;
    }

    private function mockGuzzle(array $responses)
    {
        $stack = HandlerStack::create(new MockHandler($responses));
        $client = new Client(['handler' => $stack]);
        $client->transactionHistory = [];
        $stack->push(Middleware::history($client->transactionHistory));

        return $client;
    }
}
