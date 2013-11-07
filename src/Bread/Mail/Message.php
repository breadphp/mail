<?php
namespace Bread\Mail;

use Mail;
use Mail_mime;
use Bread\Configuration\Manager as Configuration;

class Message
{

    protected $from;

    protected $to = array();

    protected $cc = array();

    protected $bcc = array();

    protected $subject;

    protected $body;

    protected $isHTML = false;
    
    protected $headers = array();

    /**
     * The file name of the file to attach or the file contents itself
     */
    protected $attachments = array();

    public function __construct($from = null, $subject = null, $body = null, array $to = array(), array $cc = array(), array $bcc = array(), array $attachments = array())
    {
        $this->from = $from;
        $this->to = $to;
        $this->cc = $cc;
        $this->bcc = $bcc;
        $this->subject = $subject;
        $this->body = $body;
        $this->attachments = $attachments;
    }

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
        $this->validate($property, $null = null);
        unset($this->$property);
    }

    public function addTo($to)
    {
        $this->to = array_unique(array_merge($this->to, (array) $to));
    }

    public function addCc($cc)
    {
        $this->cc = array_unique(array_merge($this->cc, (array) $cc));
    }

    public function addBcc($bcc)
    {
        $this->bcc = array_unique(array_merge($this->bcc, (array) $bcc));
    }

    public function addAttachment($attachment)
    {
        $this->attachments = array_unique(array_merge($this->attachments, (array) $attachment));
    }

    public function send()
    {
        if (empty($this->to) && empty($this->cc) && empty($this->bcc)) {
            return false;
        }
        $smtpHeaders = array(
            'From' => $this->from,
            'Subject' => $this->subject,
            'To' => $this->to,
            'Cc' => $this->cc,
            'Bcc' => $this->bcc,
            'Content-Type' => $this->isHTML ? 'text/html' : 'text/plain'
        );
        $mime = new Mail_mime();
        if ($this->isHTML) {
            $mime->setHTMLBody($this->body);
        } else {
            $mime->setTXTBody($this->body);
        }
        foreach ($this->attachments as $file) {
            // TODO content type
            // $finfo = new finfo(FILEINFO_MIME_TYPE);
            // $type = trim($finfo->buffer($file) . PHP_EOL);
            $mime->addAttachment($file);
        }
        // For versions older than 1.6.0 Mail_Mime::get() has to be called before Mail_Mime::headers().
        $body = $mime->getMessage();
        $mail = Mail::factory('smtp', Configuration::get(static::class, 'smtp.params'));
        return $mail->send(array_merge($this->to, $this->cc, $this->bcc), $mime->headers($smtpHeaders), $body);
    }
}