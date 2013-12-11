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
            $mail->from($message->from->email,$message->from->name);
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
                    $body = preg_replace("/(---BEST-body-BEGIN---.*---BEST-body-END---)?/ms", '', $body);
                    $body = preg_replace("/(On [\d\/]* [\d:]*,.*wrote:.*)$/m", '', $body);
                    $body = preg_replace("/(Il giorno [\d]* [\w]* [\d]* [\d:]*,.*scritto:.*)$/ms", '', $body);
                    $body = preg_replace("/(^>.*(\n|$))+/mi", '', $body);
                    $body = preg_replace("/(-+Original body-+\W{1})|(-+Messaggio originale-+)/", '', $body);
                    $body = preg_replace("/(From|Da)(: .*\W{1})/", '', $body);
                    $body = preg_replace("/(Sent|Data)(: .*\W{1})/", '', $body);
                    $body = preg_replace("/(To|A)(: .*\W{1})/", '', $body);
                    $body = preg_replace("/(Cc: .*\W{1})/", '', $body);
                    $body = trim(preg_replace("/(Subject|Ogg|Oggetto)(: .*)($|\W{2,})/", '', $body));
                    $mail->body = $body;
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
