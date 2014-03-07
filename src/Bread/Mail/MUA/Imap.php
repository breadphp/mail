<?php
namespace Bread\Mail\MUA;

use Bread\Helpers\HTML\Page;
use Bread\Mail\Message;
use Bread\Media\File\Model as File;
use Bread\Types\DateTime;
use ezcMailImapTransport;
use ezcMailParser;
use ezcMailText;
use ezcMailFile;

class Imap
{
    public static function fetchMail($server, $username, $password, $mailbox = 'INBOX', $flag = 'UNSEEN')
    {
        $imap = new ezcMailImapTransport($server);
        $imap->authenticate($username, $password);
        $imap->selectMailbox($mailbox);
        $set = $imap->fetchByFlag($flag);
        $parser = new ezcMailParser();
        $messages = $parser->parseMail($set);
        $mailFetch = array();
        foreach ($messages as $message) {
            $mail = new Message();
            $mail->date = (new DateTime())->setTimestamp($message->timestamp);
            $mail->from($message->from->email, $message->from->name);
            $mail->subject = $message->subject;
            $mail->type = Message::TEXT_PLAIN;
            foreach ($message->to as $to) {
                $mail->addTo($to->email, $to->name);
            }
            foreach ($message->cc as $cc) {
                $mail->addCc($cc->email, $cc->name);
            }
            foreach ($message->fetchParts() as $part) {
                if ($part instanceof ezcMailText) {
                    $body = $part->generateBody();
                    if ($part->subType === 'html') {
                        $page = new Page(html_entity_decode($body));
                        $page->query('blockquote')->remove();
                        $body = htmlspecialchars_decode(strip_tags((string) $page));
                        $mail->type = Message::TEXT_HTML;
                    }
                    $body = preg_replace("/(-+Original body-+\r?\n|-+Messaggio originale-+\r?\n)?(From: .*\r?\n|Da: .*\r?\n)?(Sent: .*\r?\n|Data: .*\r?\n|Inviato: .*\r?\n)?(To: .*\r?\n|A: .*\r?\n)?(Cc: .*\r?\n)?(Subject: .*\r?\n|Ogg: .*\r?\n|Oggetto: .*\r?\n)/ms", "", $body);
                    $body = preg_replace("/(---BEST-MESSAGE-BEGIN---.*---BEST-MESSAGE-END---)?/ms", '', $body);
                    $mail->body = trim($body);
                } elseif ($part instanceof ezcMailFile) {
                    $file = new File();
                    $file->name = $part->contentDisposition->fileName;
                    $file->data = fopen($part->fileName, 'r');
                    $file->type = "{$part->contentType}/{$part->mimeType}";
                    $mail->addAttachment($file);
                }
            }
            $mailFetch[] = $mail;
        }
        return $mailFetch;
    }
}
