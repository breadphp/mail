<?php
namespace Bread\Mail;

use Bread\Configuration\Manager as Configuration;
use PHPMailer;

class Message
{

    const TEXT_PLAIN = 0;

    const TEXT_HTML = 1;

    protected $from;

    protected $fromName;

    protected $to = array();

    protected $cc = array();

    protected $bcc = array();

    protected $subject;

    protected $body;

    protected $type;

    /**
     * The file name of the file to attach or the file contents itself
     */
    protected $attachments = array();

    public function __construct(array $params = array())
    {
        $this->type = static::TEXT_PLAIN;
        foreach ($params as $attribute => $value) {
            $this->$attribute = $value;
        }
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

    public function from($from, $name = '')
    {
        $this->from = $from;
        $this->fromName = name;
    }

    public function addTo($to, $name = '')
    {
        $this->to = array_merge($this->to, array(
            $to => $name
        ));
    }

    public function addCc($cc, $name = '')
    {
        $this->cc = array_merge($this->cc, array(
            $cc => $name
        ));
    }

    public function addBcc($bcc, $name = '')
    {
        $this->bcc = array_merge($this->bcc, array(
            $bcc => $name
        ));
    }

    public function addAttachment($attachment)
    {
        $this->attachments = array_merge($this->attachments, (array) $attachment);
    }

    public function send()
    {
        $mail = new PHPMailer();
        $mail->CharSet = 'UTF-8';
        $mail->SMTPAuth = true;
        $mail->isSMTP();
        $mail->Host = Configuration::get(__CLASS__, 'smtp.host');
        $mail->Username = Configuration::get(__CLASS__, 'smtp.username');
        $mail->Password = Configuration::get(__CLASS__, 'smtp.password');
        $mail->From = $this->from;
        $mail->FromName = $this->fromName;
        $mail->Subject = $this->subject;
        $mail->Body = $this->body;
        $mail->isHTML($this->type);
        foreach ($this->to as $to => $name) {
            $mail->addAddress($to, $name);
        }
        foreach ($this->cc as $cc => $name) {
            $mail->addCC($cc, $name);
        }
        foreach ($this->bcc as $bcc => $name) {
            $mail->addBCC($bcc, $name);
        }
        return $mail->send() ? true : $mail->ErrorInfo;
    }
}