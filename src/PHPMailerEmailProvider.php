<?php

declare(strict_types=1);

namespace Simphle\Messaging\Email\Provider;

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;
use Simphle\Messaging\Email\EmailMessageInterface;
use Simphle\Messaging\Email\EmailMessageValidator;
use Simphle\Messaging\Email\Exception\EmailTransportException;
use Simphle\Messaging\Email\SMTPOptions;

class PHPMailerEmailProvider implements EmailProviderInterface
{
    use EmailMessageValidator;

    private PHPMailer $mailer;

    public function __construct(
        private readonly SMTPOptions $options,
        private readonly LoggerInterface $logger = new NullLogger()
    ) {
        if (!class_exists(PHPMailer::class)) {
            throw new RuntimeException('PHPMailer is not installed on this system');
        }
        $this->mailer = new PHPMailer(true);
        $this->mailer->isSMTP();
        $this->mailer->Host = $this->options->host;
        $this->mailer->Port = $this->options->port;

        if (
            strlen($this->options->username) > 0
            && strlen($this->options->password) > 0
        ) {
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = $this->options->username;
            $this->mailer->Password = $this->options->password;
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        }
    }

    public function setDebug(bool $enabled = false): void
    {
        if ($enabled) {
            $this->mailer->SMTPDebug = SMTP::DEBUG_SERVER;
        }
    }

    public function send(EmailMessageInterface $message, array $options = []): void
    {
        try {
            // Standard validation
            [$sender, $recipients, $subject, $html, $text] = $this->validate($message);

            $this->mailer->setFrom(
                $sender->address,
                $sender->name ?? ''
            );

            $this->mailer->Subject = $subject;

            foreach ($recipients as $recipient) {
                $this->mailer->addAddress(
                    $recipient->address,
                    $recipient->name ?? ''
                );
            }

            $replyTo = $message->getReplyTo();
            if (!is_null($replyTo)) {
                $this->mailer->addReplyTo(
                    $replyTo->address
                );
            }

            $cc = $message->getCC();
            foreach ($cc as $recipient) {
                $this->mailer->addCC(
                    $recipient->address,
                    $recipient->name ?? ''
                );
            }

            $bcc = $message->getCC();
            foreach ($bcc as $recipient) {
                $this->mailer->addCC(
                    $recipient->address,
                    $recipient->name ?? ''
                );
            }

            $attachments = $message->getAttachments();
            foreach ($attachments as $attachment) {
                $this->mailer->addAttachment(
                    path: $options['baseDir'] . DIRECTORY_SEPARATOR . $attachment->path,
                    name: $attachment->name,
                    disposition: $attachment->inline ? 'inline' : 'attachment'
                );
            }

            /** @psalm-suppress RiskyTruthyFalsyComparison */
            if (!empty($html)) {
                // Auto HTML: Text version will be generated here,
                // plus images are converted and attached
                // $this->mailer->msgHTML($html, $options['baseDir'] ?? '');
                // @todo Maybe set an option here like autoHtml true/false

                // Manual HTML
                $this->mailer->isHTML();
                $this->mailer->Body = $html;
                /** @psalm-suppress RiskyTruthyFalsyComparison */
                if (!empty($text)) {
                    $this->mailer->AltBody = $text;
                }
            } else {
                $this->mailer->isHTML(false);
                $this->mailer->Body = $text;
            }
            $this->mailer->send();
        } catch (Exception $e) {
            $this->logger->error('[PHPMailer] Message could not be sent', [
                'error' => $e->getMessage()
            ]);
            throw new EmailTransportException($e->getMessage());
        }
    }
}
