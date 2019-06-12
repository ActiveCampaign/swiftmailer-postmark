<?php

use PHPUnit\Framework\TestCase;
use Postmark\ThrowExceptionOnFailurePlugin;

require_once __DIR__ . '/../vendor/autoload.php';

class ThrowExceptionOnFailurePluginTest extends TestCase {

    /**
     * @doesNotPerformAssertions
     */
    public function testValidResponseThrowsNoException()
    {
        $valid = true;
        $event = new \Swift_Events_ResponseEvent(new \Postmark\Transport('SERVER_TOKEN'), 'success', $valid);

        $plugin = new ThrowExceptionOnFailurePlugin();
        $plugin->responseReceived($event); // no exception
    }

    public function testInvalidResponseThrowsException()
    {
        $valid = false;
        $event = new \Swift_Events_ResponseEvent(new \Postmark\Transport('SERVER_TOKEN'), 'invalid response', $valid);

        $plugin = new ThrowExceptionOnFailurePlugin();

        $this->expectException(\Swift_TransportException::class);
        $this->expectExceptionMessage('invalid response');

        $plugin->responseReceived($event);
    }
}
