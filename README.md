# SMTP Transport Provider for Simphle Messaging

SMTP provider for [Simphle Messaging](https://github.com/vtardia/simphle-messaging) based on [PHPMailer](https://github.com/PHPMailer/PHPMailer).

## Install

```shell
composer require vtardia/simphle-messaging-smtp
```

## Usage

```php
use Simphle\Messaging\Email\Provider\PHPMailerEmailProvider;
use Simphle\Messaging\Email\SMTPOptions;

try {
    $message = /* Create a message here... */
    $mailer = new PHPMailerEmailProvider(
        new SMTPOptions(
            host: 'smtp.mailer.com',
            port: 25,
            username: 'you',
            password: 'YourPassword'
        ),
        /* optional PRS logger */
    );
    
    // Send the email
    $mailer->send($message /*, [some, options]*/);
} catch (InvalidMessageException $e) {
    // Do something...
} catch (EmailTransportException $e) {
    // Do something else...
}
```
