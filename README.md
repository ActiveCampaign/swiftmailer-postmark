# swiftmailer-postmark 
[![Build Status](https://travis-ci.org/wildbit/swiftmailer-postmark.svg?branch=master)](https://travis-ci.org/wildbit/swiftmailer-postmark)

An official Swiftmailer Transport for Postmark.

Send mail through Postmark from your favorite PHP frameworks!

You're just steps away from super simple sending via Postmark:

##### 1. Include this package in your project:

```bash
composer require wildbit/swiftmailer-postmark
```
##### 2. Use the transport to send a message:

```php
<?php
//import the transport from the standard composer directory:
require_once('./vendor/autoload.php');

$transport = new \Postmark\Transport('<SERVER_TOKEN>');
$mailer = new Swift_Mailer($transport);

//Instantiate the message you want to send.
$message = (new Swift_Message('Hello from Postmark!'))
  ->setFrom(['john@example.com' => 'John Doe'])
  ->setTo(['jane@example.com'])
  ->setBody('<b>A really important message from our sponsors.</b>', 'text/html')
  ->addPart('Another important message from our sponsors.','text/plain');

//Add some attachment data:
$attachmentData = 'Some attachment data.';
$attachment = new Swift_Attachment($attachmentData, 'my-file.txt', 'application/octet-stream');

$message->attach($attachment);

//Send the message!
$mailer->send($message);
?>
```

##### 3. Throw exceptions on Postmark api errors:

```php
$transport = new \Postmark\Transport('<SERVER_TOKEN>');
$transport->registerPlugin(new \Postmark\ThrowExceptionOnFailurePlugin());

$message = new Swift_Message('Hello from Postmark!');
$mailer->send($message); // Exception is throw when response !== 200
```

##### 4. Use default headers:

You can set default headers at Transport-level, to be set on every message, unless overwritten.

```php
$defaultHeaders = ['X-PM-Tag' => 'my-tag'];

$transport = new \Postmark\Transport('<SERVER_TOKEN>', $defaultHeaders);

$message = new Swift_Message('Hello from Postmark!');

// Overwriting default headers
$message->getHeaders()->addTextHeader('X-PM-Tag', 'custom-tag');
```

##### 5. Setting the Message Stream:

By default, the "outbound" transactional stream will be used when sending messages.

```php
// Change the default stream for every message via Default Headers
$transport = new \Postmark\Transport('<SERVER_TOKEN>', ['X-PM-Message-Stream' => 'your-custom-stream']);

$message = new Swift_Message('Hello from Postmark!');

// Overwrite the default stream for a specific message by setting the header
$message->getHeaders()->addTextHeader('X-PM-Message-Stream', 'another-stream');
```