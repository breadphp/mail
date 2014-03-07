<?php
namespace Bread\Mail;

use Bread\Configuration\Manager as Configuration;
use PHPMailer;
use Bread\Media\File\Model as File;
use Bread\REST;
use Bread\Storage\Collection;
use Bread\Types\DateTime;

class Message extends REST\Model
{

    const TEXT_PLAIN = 0;

    const TEXT_HTML = 1;

    const ENCODING = 'base64';

    protected $messageId;

    protected $from;

    protected $to;

    protected $cc;

    protected $bcc;

    protected $headers;

    protected $subject;

    protected $body;

    protected $type;

    protected $date;

    protected $attachments;

    public function __construct(array $params = array())
    {
        $this->to = new Collection();
        $this->cc = new Collection();
        $this->bcc = new Collection();
        $this->headers = new Collection();
        $this->attachments = new Collection();
        $this->type = static::TEXT_PLAIN;
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
        $mail->From = $this->from->mail;
        $mail->FromName = $this->from->cn;
        $mail->Subject = $this->subject;
        $mail->Body = $this->body;
        $mail->isHTML($this->type);
        if ($this->messageId) {
            $mail->MessageID = $this->messageId;
        }
        foreach ($this->to as $to) {
            $mail->addAddress($to->mail, $to->cn);
        }
        foreach ($this->cc as $cc) {
            $mail->addCC($cc->mail, $cc->cn);
        }
        foreach ($this->bcc as $bcc) {
            $mail->addBCC($bcc->mail, $bcc->cn);
        }
        foreach ($this->attachments as $attachment) {
            $mail->addStringEmbeddedImage(stream_get_contents($attachment->data), $attachment->name, $attachment->name, static::ENCODING, $attachment->type);
        }
        foreach ($this->headers as $header) {
            $explode = explode(":", $header, 2);
            $mail->addCustomHeader($explode[0], $explode[1]);
        }
        if ($mail->send()) {
            $this->messageId = $mail->getLastMessageID();
            return true;
        }
        return false;
    }
}

Configuration::defaults('Bread\Mail\Message', array(
    'properties' => array(
        'messageId' => array(
            'type' => 'string'
        ),
        'from' => array(
            'type' => 'Bread\REST\Behaviors\ARO'
        ),
        'to' => array(
            'type' => 'Bread\REST\Behaviors\ARO',
            'multiple' => true
        ),
        'cc' => array(
            'type' => 'Bread\REST\Behaviors\ARO',
            'multiple' => true
        ),
        'bcc' => array(
            'type' => 'Bread\REST\Behaviors\ARO',
            'multiple' => true
        ),
        'headers' => array(
            'type' => 'string',
            'multiple' => true
        ),
        'subject' => array(
            'type' => 'string'
        ),
        'body' => array(
            'type' => 'string'
        ),
        'type' => array(
            'type' => 'string'
        ),
        'date' => array(
            'type' => 'Bread\Types\DateTime'
        ),
        'attachments' => array(
            'type' => 'Bread\Media\File\Model',
            'multiple' => true
        )
    )
));
