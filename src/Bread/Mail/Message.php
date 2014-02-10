<?php
namespace Bread\Mail;

use Bread\Configuration\Manager as Configuration;
use Bread\Types\DateTime;
use PHPMailer;
use Bread\Media\File\Model as File;

class Message
{

    const TEXT_PLAIN = 0;

    const TEXT_HTML = 1;

    const ENCODING = 'base64';

    protected $from;

    protected $fromName;

    protected $to = array();

    protected $cc = array();

    protected $bcc = array();

    protected $subject;

    protected $body;

    protected $type;

    protected $date;

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
        unset($this->$property);
    }

    public function from($from, $name = '')
    {
        $this->from = $from;
        $this->fromName = $name;
    }

    public function addTo($to, $name = '')
    {
        $this->to[$to] = $name;
    }

    public function addCc($cc, $name = '')
    {
        $this->cc[$cc] = $name;
    }

    public function addBcc($bcc, $name = '')
    {
        $this->bcc[$bcc] = $name;
    }

    public function addAttachment(File $attachment)
    {
        $this->attachments[] = $attachment;
    }

    public function send()
    {
        $this->date = new DateTime();
        $mail = new PHPMailer();
        $mail->isSMTP();
        $mail->Encoding = 'quoted-printable';
        $mail->CharSet = 'UTF-8';
        $mail->SMTPAuth = Configuration::get(__CLASS__, 'smtp.requireAuthentication');
        $mail->Host = Configuration::get(__CLASS__, 'smtp.host');
        $mail->Username = Configuration::get(__CLASS__, 'smtp.username');
        $mail->Password = Configuration::get(__CLASS__, 'smtp.password');
        $mail->SMTPSecure = Configuration::get(__CLASS__, 'smtp.secure') ? : ""; //Options: "", "ssl" or "tls"
        $mail->AuthType = Configuration::get(__CLASS__, 'smtp.auth') ? : "LOGIN"; //Options:LOGIN (default), PLAIN, NTLM, CRAM-MD5
        $mail->Realm = Configuration::get(__CLASS__, 'smtp.realm');
        $mail->Workstation = Configuration::get(__CLASS__, 'smtp.username');
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
        foreach ($this->attachments as $attachment) {
            $mail->addStringEmbeddedImage(stream_get_contents($attachment->data), $attachment->name, $attachment->name, static::ENCODING, $attachment->type);
        }
        return $mail->send() ? true : $mail->ErrorInfo;
    }
}
