<?php
namespace Bread\Mail\Drivers;

use Bread\Configuration\Manager as Configuration;
use Bread\Mail\Interfaces;
use Bread\Mail\Model as Mail;
use Bread\Types\DateTime;

class PHPMailer implements Interfaces\Driver
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
        $mail = new PHPMailer();
        $mail->Host = $this->params['host'];
        $mail->Username = $this->params['user'];
        $mail->Password = $this->params['password'];
        $mail->SMTPAuth = Configuration::get(Mail::class, 'connection.requireAuthentication');
        $mail->SMTPSecure = Configuration::get(Mail::class, 'conenction.secure') ?  : ""; // Options: "", "ssl" or "tls"
        $mail->AuthType = Configuration::get(Mail::class, 'connection.auth') ?  : "LOGIN"; // Options:LOGIN (default), PLAIN, NTLM, CRAM-MD5
        $mail->Realm = Configuration::get(Mail::class, 'connection.realm');
        $mail->Workstation = $mail->Username;
        $mail->Encoding = 'quoted-printable';
        $mail->CharSet = 'UTF-8';

        $model->date = new DateTime();
        $mail->isSMTP();
        $mail->From = $model->from->address;
        $mail->FromName = $model->from->name;
        $mail->Subject = $model->subject;
        $mail->Body = $model->body;
        $mail->isHTML($model->type);
        if ($model->messageId) {
            $mail->MessageID = $model->messageId;
        }
        foreach ($model->to as $to) {
            $mail->addAddress($to->address, $to->name);
        }
        foreach ($model->cc as $cc) {
            $mail->addCC($cc->address, $cc->name);
        }
        foreach ($model->bcc as $bcc) {
            $mail->addBCC($bcc->address, $bcc->name);
        }
        foreach ($model->attachments as $attachment) {
            $mail->addStringAttachment(stream_get_contents($attachment->data), $attachment->name, Mail::ENCODING, $attachment->type);
        }
        foreach ($model->headers as $header) {
            $explode = explode(":", $header, 2);
            $mail->addCustomHeader(trim($explode[0]), trim($explode[1]));
        }
        if ($mail->send()) {
            $model->messageId = $mail->getLastMessageID();
            return true;
        }
        return false;
    }

    public function receive(array $params)
    {}

    public function move($message, $from, $to)
    {}
}