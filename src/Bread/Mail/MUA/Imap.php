<?php
namespace Bread\Mail\MUA;

use Bread\Mail\Message;
use Bread\Types\DateTime;
use Bread\Helpers\HTML\Page;

class Imap
{
    public static function getMail($server, $username, $password, $params = array('ALL' => true), $mailbox = 'INBOX', $flag = 'novalidate-cert')
    {
        if (! $mailbox = imap_open("{{$server}/{$flag}}$mailbox", $username, $password, 0, 0, array('DISABLE_AUTHENTICATOR' => 'GSSAPI'))) {
            return array();
        }
        return static::search($mailbox, static::normalizeSearch($params));
    }

    protected static function search($mailbox, $search)
    {
        $mail = array();
        $search = imap_search($mailbox, $search) ? imap_search($mailbox, $search) : array();
        foreach ($search as $number) {
            $message = new Message();
            $headers = imap_headerinfo($mailbox, $number);
            $message->body = static::getBody($mailbox, $number);
            foreach (get_object_vars($headers) as $attribute => $value) {
                switch ($attribute) {
                    case 'date':
                        $message->$attribute = new DateTime($value);
                        break;
                    case 'subject':
                        $message->$attribute = $value;
                        break;
                    case 'from':
                        $message->from($headers->from[0]->mailbox . '@' . $headers->from[0]->host, isset($headers->from[0]->personal) ? $headers->from[0]->personal : '');
                        break;
                    case 'to':
                        foreach ($value as $to) {
                            $message->addTo($headers->to[0]->mailbox . '@' . $headers->to[0]->host, isset($headers->to[0]->personal) ? $headers->from[0]->personal : '');
                        }
                        break;
                    case 'cc':
                        foreach ($value as $cc) {
                            $message->addCc($headers->cc[0]->mailbox . '@' . $headers->cc[0]->host, isset($headers->cc[0]->personal) ? $headers->from[0]->personal : '');
                        }
                        break;
                }
            }
            $mail[] = $message;
        }
        imap_close($mailbox);
        return $mail;
    }

    protected static function normalizeSearch($params)
    {
        $conditions = array();
        foreach ($params as $criteria => $value) {
            switch (strtoupper($criteria)) {
                case 'BEFORE':
                case 'SINCE':
                case 'ON':
                    $value = $value->format("d-M-Y");
                case 'BODY':
                case 'KEYWORD':
                case 'SUBJECT':
                case 'TEXT':
                case 'UNKEYWORD':
                    $conditions[] = $criteria . " \"{$value}\"";
                    break;
                case 'BCC':
                case 'CC':
                case 'FROM':
                case 'TO':
                    $value = array_map(function ($address) use($criteria) {
                        return $criteria . " \"{$address}\"";
                    }, (array) $value);
                    $conditions[] = implode(' ', $value);
                    break;
                default:
                    $conditions[] = $criteria;
            }
        }
        return implode(' ', $conditions);
    }

    protected static function getBody($mailbox, $number)
    {
        $structure = imap_fetchstructure($mailbox, $number);
        $encoding = $structure->encoding;
        switch ($structure->subtype) {
            case 'ALTERNATIVE':
                if($structure->parts[0]->subtype === 'PLAIN') {
                    $message = imap_fetchbody($mailbox, $number, '1');
                } else {
                    $message = imap_fetchbody($mailbox, $number, '1');
                    $encoding = $structure->parts[0]->encoding;
                    $page = new Page($message);
                    $page->query('blockquote')->remove();
                    $message = strip_tags(htmlspecialchars_decode((string) $page));
                }
                break;
            case 'HTML':
                $page = new Page(imap_body($mailbox, $number));
                $page->query('blockquote')->remove();
                $message = htmlspecialchars_decode((string) $page);
                break;
            case 'PLAIN':
                $message = imap_body($mailbox, $number);
                break;
            case 'MIXED':
//                 foreach ($structure->parts as $i=>$part) {
//                     switch ($part->)
//                 }
        }
        $message = static::decodeBody($message, $structure->encoding);
        $message = preg_replace("/(---BEST-MESSAGE-BEGIN---.*---BEST-MESSAGE-END---)?/ms", '', $message);
        $message = preg_replace("/(On [\d\/]* [\d:]*,.*wrote:.*)$/m", '', $message);
        $message = preg_replace("/(Il giorno [\d]* [\w]* [\d]* [\d:]*,.*scritto:.*)$/ms", '', $message);
        $message = trim(preg_replace("/(^>.*(\n|$))+/mi", '', $message));
        $message = trim(preg_replace("/(-+Original Message-+\W{1})|(-+Messaggio originale-+)/", '', $message));
        $message = trim(preg_replace("/(From|Da)(: .*\W{1})/", '', $message));
        $message = trim(preg_replace("/(Sent|Data)(: .*\W{1})/", '', $message));
        $message = trim(preg_replace("/(To|A)(: .*\W{1})/", '', $message));
        $message = trim(preg_replace("/(Cc: .*\W{1})/", '', $message));
        $message = trim(preg_replace("/(Subject|Ogg|Oggetto)(: .*)($|\W{2,})/", '', $message));
        return $message;
    }

    /**
     * 0	7BIT
     * 1	8BIT
     * 2	BINARY
     * 3	BASE64
     * 4	QUOTED-PRINTABLE
     * 5	OTHER
     */
    protected static function decodeBody($body, $encoding)
    {
        if ($encoding === 3) {
            return base64_decode($body);
        } elseif ($encoding === 4) {
            return quoted_printable_decode($body);
        } else {
            return $body;
        }
    }
}
