<?php
namespace Bread\Mail\Interfaces;

use Bread\Mail\Model;

interface Driver
{

    public function send(Model $model);

    public function receive(array $params);

    public function move($message, $from, $to);
}