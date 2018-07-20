<?php

require_once __DIR__ . "/../vendor/autoload.php";

class MailPostmarkTransportTest extends PHPUnit_Framework_TestCase {

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

		$attachment = Swift_Attachment::newInstance('This is the plain text attachment.', 'hello.txt', 'text/plain');
		$attachment2 = Swift_Attachment::newInstance('This is the plain text attachment.', 'hello.txt', 'text/plain');
		$attachment2->setDisposition('inline');

		$message->attach($attachment);
		$message->attach($attachment2);
		$message->setPriority(1);

		$headers = $message->getHeaders();
		$headers->addTextHeader('X-PM-Tag', 'movie-quotes');
		$messageId = $headers->get('Message-ID')->getId();

		$transport = new PostmarkTransportStub('TESTING_SERVER');

		$client = $this->getMock('GuzzleHttp\Client', array('request'));
		$transport->setHttpClient($client);

		$o = PHP_OS;
		$v = phpversion();

		$client->expects($this->once())
		       ->method('request')
		       ->with($this->equalTo('POST'), 
		       	    $this->equalTo('https://api.postmarkapp.com/email'),
			        $this->equalTo([
				        'headers' => [
					        'X-Postmark-Server-Token' => 'TESTING_SERVER',
					        'User-Agent' => "swiftmailer-postmark (PHP Version: $v, OS: $o)",
					        'Content-Type' => 'application/json'
				        ],
				        'json' => [
					        'From' => '"Johnny #5" <johnny5@example.com>',
					        'To' => '"A. Friend" <you@example.com>,you+two@example.com',
					        'Cc' => 'another+1@example.com,"Extra 2" <another+2@example.com>',
					        'Bcc' => 'another+3@example.com,"Extra 4" <another+4@example.com>',
					        'Subject' => 'Is alive!',
					        'Tag' => 'movie-quotes',
					        'TextBody' => 'Doo-wah-ditty.',
					        'HtmlBody' => '<q>Help me Rhonda</q>',
					        'Headers' => [
						        ['Name' => 'Message-ID', 'Value' => '<' . $messageId . '>'],
						        ['Name' => 'X-PM-KeepID', 'Value' => 'true'],
						        ['Name' => 'X-Priority', 'Value' => '1 (Highest)'],
					        ],
					        'Attachments' => [
						        [
							        'ContentType' => 'text/plain',
							        'Content' => 'VGhpcyBpcyB0aGUgcGxhaW4gdGV4dCBhdHRhY2htZW50Lg==',
							        'Name' => 'hello.txt'
						        ],
						        [
							        'ContentType' => 'text/plain',
							        'Content' => 'VGhpcyBpcyB0aGUgcGxhaW4gdGV4dCBhdHRhY2htZW50Lg==',
							        'Name' => 'hello.txt',
							        'ContentID' => 'cid:'.$attachment2->getId()
						        ],
					        ],
				        ],
                        'http_errors' => false,
			        ])
		       );

		$transport->send($message);
	}

	public function testEvents()
    {
        $message = new Swift_Message();
        $message->setFrom('johnny5@example.com', 'Johnny #5');
        $message->setSubject('Some Subject');
        $message->addTo('you@example.com', 'A. Friend');
        $transport = new PostmarkTransportStub('TESTING_SERVER');

        $transport->registerPlugin(new Swift_Plugins_RedirectingPlugin('test@test.com'));

        $client = $this->getMock('GuzzleHttp\Client', array('request'));
        $transport->setHttpClient($client);

        $o = PHP_OS;
        $v = phpversion();

        $client->expects($this->once())
            ->method('request')
            ->with($this->equalTo('POST'),
                $this->equalTo('https://api.postmarkapp.com/email'),
                $this->equalTo([
                    'headers' => [
                        'X-Postmark-Server-Token' => 'TESTING_SERVER',
                        'User-Agent' => "swiftmailer-postmark (PHP Version: $v, OS: $o)",
                        'Content-Type' => 'application/json'
                    ],
                    'json' => [
                        'From' => '"Johnny #5" <johnny5@example.com>',
                        'To' => 'test@test.com',
                        'Subject' => 'Some Subject',
                        'TextBody' => null,
                        'Headers' => [
                            ['Name' => 'Content-Transfer-Encoding', 'Value' => 'quoted-printable'],
                            ['Name' => 'Message-ID', 'Value' => '<' . $message->getId() . '>'],
                            ['Name' => 'X-PM-KeepID', 'Value' => 'true'],
                        ],
                    ],
                    'http_errors' => false,
                ])
            );

        $transport->send($message);
    }
}

class PostmarkTransportStub extends Postmark\Transport {
	protected $client;

	protected function getHttpClient() {
		return $this->client;
	}

	public function setHttpClient($client) {
		$this->client = $client;
	}
}
