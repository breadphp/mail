<?php
namespace Bread\Mail;

use Bread\Mail\Mailbox\Model as Mailbox;
use Bread\Media\File\Model as File;

class Model
{

    const TEXT_PLAIN = 0;

    const TEXT_HTML = 1;

    const ENCODING = 'base64';

    protected $messageId;

    protected $mailboxNumber;

    protected $from;

    protected $to = array();

    protected $cc = array();

    protected $bcc = array();

    protected $headers = array();

    protected $subject;

    protected $body;

    protected $type;

    protected $date;

    protected $attachments = array();

    public function __set($property, $value)
    {
        $this->$property = $value;
    }

    public function __get($property)
    {
        return $this->$property;
    }

    public function __isset($property)
    {
        return isset($this->$property);
    }

    public function __unset($property)
    {
        unset($this->$property);
    }

    public function from($from, $name = '')
    {
        $this->from = new Mailbox($from, $name);
    }

    public function addTo($to, $name = '')
    {
        $this->to[] = new Mailbox($to, $name);
    }

    public function addCc($cc, $name = '')
    {
        $this->cc[] = new Mailbox($cc, $name);
    }

    public function addBcc($bcc, $name = '')
    {
        $this->bcc[] = new Mailbox($bcc, $name);
    }

    public function addHeader($header, $value)
    {
        $this->headers[$header] = $value;
    }

    public function addAttachment(File $attachment)
    {
        $this->attachments[] = $attachment;
    }

    public function send()
    {
        return Driver::driver(__CLASS__)->send($this);
    }

    public static function receive(array $params = array())
    {
        return Driver::driver(__CLASS__)->receive($params);
    }

    public static function move($message, $from, $to)
    {
        return Driver::driver(__CLASS__)->move($message, $from, $to);
    }
}