<?php

namespace Postmark;

class ThrowExceptionOnFailurePlugin implements \Swift_Events_ResponseListener
{
    public function responseReceived(\Swift_Events_ResponseEvent $event)
    {
        if (!$event->isValid()) {
            throw new \Swift_TransportException($event->getResponse());
        }
    }
}
