<?php
namespace Bread\Mail\Template;

use Bread\REST;
use Bread\Configuration\Manager as Configuration;

class Model extends REST\Model
{

    protected $name;

    protected $subject;

    protected $body;

    protected $html;

    protected $bodyVars;

    protected $subjectVars;
}

Configuration::defaults('Bread\Mail\Template\Model', array(
    'properties' => array(
        'name' => array(
            'type' => 'string',
            'unique' => true
        ),
        'subject' => array(
            'type' => 'text'
        ),
        'body' => array(
            'type' => 'text'
        ),
        'html' => array(
            'type' => 'integer'
        ),
        'bodyVars' => array(
            'type' => 'string',
            'multiple' => true
        ),
        'subjectVars' => array(
            'type' => 'string',
            'multiple' => true
        )
    )
));