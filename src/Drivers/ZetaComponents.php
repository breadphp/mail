<?php
namespace Bread\Mail\Drivers;

use Bread\Configuration\Manager as Configuration;
use Bread\Mail\Interfaces;
use Bread\Mail\Mailbox\Model as Mailbox;
use Bread\Mail\Model as Mail;
use Bread\Media\File\Model as File;
use Bread\Types\DateTime;
use ezcMail;
use ezcMailAddress;
use ezcMailStreamFile;
use ezcMailImapTransportOptions;
use ezcMailImapTransport;
use ezcMailMultipartMixed;
use ezcMailNoSuchMessageException;
use ezcMailSmtpTransport;
use ezcMailParser;
use ezcMailText;
use ezcMailFile;

class ZetaComponents implements Interfaces\Driver
{

    protected $params;

    public function __construct($uri)
    {
        $this->params = array(
            'user' => parse_url($uri, PHP_URL_USER),
            'password' => parse_url($uri, PHP_URL_PASS),
            'host' => parse_url($uri, PHP_URL_HOST),
            'port' => parse_url($uri, PHP_URL_PORT)
        );
    }

    public function send(Mail $model)
    {
        if (Configuration::get(Mail::class, 'connection.requireAuthentication')) {
            $smtp = new ezcMailSmtpTransport($this->params['host'], $this->params['user'], $this->params['password'], $this->params['port']);
        } else {
            $smtp = new ezcMailSmtpTransport($this->params['host'], "", "", $this->params['port']);
        }
        $model->date = new DateTime();
        $mail = new ezcMail();
        $mail->from = new ezcMailAddress($model->from->address, $model->from->name);
        $mail->subject = $model->subject;
        $mail->body = new ezcMailMultipartMixed();
        $body = new ezcMailText($model->body, 'UTF-8');
        if ($model->type === Mail::TEXT_HTML) {
            $body->subType = "html";
        }
        $mail->body->appendPart($body);
        if ($model->attachments) {
            foreach ($model->attachments as $attachment) {
                $mail->body->appendPart(new ezcMailStreamFile($attachment->name, $attachment->data, $attachment->type));
            }
        }
        if ($model->messageId) {
            $mail->messageId = $model->messageId;
        }
        foreach ($model->to as $to) {
            $mail->addTo(new ezcMailAddress($to->address, $to->name));
        }
        foreach ($model->cc as $cc) {
            $mail->addCc(new ezcMailAddress($cc->address, $cc->name));
        }
        foreach ($model->bcc as $bcc) {
            $mail->addBcc(new ezcMailAddress($bcc->address, $bcc->name));
        }
        foreach ($model->headers as $header => $value) {
            $mail->setHeader($header, $value);
        }
        if ($model->to || $model->cc || $model->bcc) {
            $smtp->send($mail);
            if (!$model->messageId) {
                $model->messageId = $mail->getHeader('Message-Id');
            }
            foreach ($mail->body->getParts() as $part) {
                if ($part instanceof ezcMailStreamFile) {
                    rewind($part->stream);
                }
            }
            $imap = new ezcMailImapTransport($this->params['host'], $this->params['port']);
            $imap->authenticate($this->params['user'], $this->params['password']);
            $imap->append("Sent", $mail->generate(), array(
                "SEEN"
            ));
            $imap->disconnect();
            return true;
        }
    }

    public function receive(array $params = array())
    {
        $options = new ezcMailImapTransportOptions();
        $options->uidReferencing = false;
        $imap = new ezcMailImapTransport($this->params['host'], $this->params['port'], $options);
        $imap->authenticate($this->params['user'], $this->params['password']);
        $imap->selectMailbox(isset($params['mailbox']) ? $params['mailbox'] : "INBOX");
        if (isset($params['messageId'])) {
            try {
                $set = $imap->searchMailbox($params['messageId']);
            } catch (ezcMailNoSuchMessageException $exception) {
                return array();
            }
        } else {
            $set = $imap->fetchByFlag(isset($params['flag']) ? $params['flag'] : "UNSEEN");
        }
        $numbers = $set->getMessageNumbers();
        $parser = new ezcMailParser();
        $messages = $parser->parseMail($set);
        $mailFetch = array();
        foreach ($messages as $i => $message) {
            $mail = new Mail();
            $mail->date = (new DateTime())->setTimestamp($message->timestamp);
            $mail->from = new Mailbox($message->from->email);
            $mail->subject = $message->subject;
            $mail->type = Mail::TEXT_PLAIN;
            $mail->messageId = $message->messageId;
            $mail->mailboxNumber = $numbers[$i];
            foreach ($message->to as $to) {
                $mail->addTo($to->email, $to->name);
            }
            foreach ($message->cc as $cc) {
                $mail->addCc($cc->email, $cc->name);
            }
            $mail->addHeader('References', $message->getHeader('References', true));
            $mail->addHeader('In-Reply-To', $message->getHeader('In-Reply-To', true));
            foreach ($message->fetchParts() as $part) {
                if ($part instanceof ezcMailText) {
                    if ($part->subType === 'html') {
                        $mail->type = Mail::TEXT_HTML;
                    }
                    $mail->body = $part->generateBody();
                } elseif ($part instanceof ezcMailFile) {
                    $file = new File();
                    $file->name = $part->contentDisposition->fileName;
                    $file->data = fopen($part->fileName, 'r');
                    $file->type = "{$part->contentType}/{$part->mimeType}";
                    $file->size = $part->size;
                    $mail->addAttachment($file);
                }
            }
            $mailFetch[] = $mail;
        }
        $imap->disconnect();
        return $mailFetch;
    }

    public function move($message, $from, $to, $flag = null)
    {
        $imap = new ezcMailImapTransport($this->params['host'], $this->params['port']);
        $imap->authenticate($this->params['user'], $this->params['password']);
        $imap->selectMailbox($from);
        if ($flag) {
            $imap->setFlag($message, $flag);
        }
        $copy = $imap->copyMessages($message, $to);
        $delete = $imap->delete($message);
        $delete = $imap->expunge();
        $imap->disconnect();
        return $copy && $delete;
    }
}
