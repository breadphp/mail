<?php
namespace Bread\Mail;

use Bread\Configuration\Manager as Configuration;
use PHPMailer;

class Message
{

    const TEXT_PLAIN = 0;

    const TEXT_HTML = 1;

    protected $from;

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
        $mail = new PHPMailer();
        $mail->isSMTP();
        $mail->Host = Configuration::get(__CLASS__, 'smtp.host');
        $mail->SMTPAuth = true;
        $mail->Username = Configuration::get(__CLASS__, 'smtp.username');
        $mail->Password = Configuration::get(__CLASS__, 'smtp.password');
        // $mail->SMTPSecure = 'tls';
        $mail->From = $this->from;
        $mail->Subject = $this->subject;
        $mail->Body = $this->body;
        $mail->isHTML($this->type);
        foreach ($this->to as $to) {
            $mail->addAddress($to);
        }
        foreach ($this->cc as $cc) {
            $mail->addCC($cc);
        }
        foreach ($this->bcc as $bcc) {
            $mail->addBCC($bcc);
        }
        return $mail->send() ? true : $mail->ErrorInfo;
    }
}