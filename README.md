# swiftmailer-postmark 
[![Build Status](https://travis-ci.org/wildbit/swiftmailer-postmark.svg?branch=master)](https://travis-ci.org/wildbit/swiftmailer-postmark)

An official Swiftmailer Transport for Postmark.

Send mail through Postmark from your favorite PHP frameworks!

You're just steps away from super simple sending via Postmark:

##### 1. Include this package in your project:

```bash
composer require wildbit/swiftmailer-postmark
```
##### 2. Construct the Postmark Transport and pass it to your `Swift_Mailer` instance:

```php
$transport = new \Postmark\Transport("<YOUR_SERVER_TOKEN>");
$mailer = new Swift_Mailer($transport);
```

##### 3. There is no step three.
