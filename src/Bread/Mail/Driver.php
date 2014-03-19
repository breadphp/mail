<?php
namespace Bread\Mail;

use Bread\Configuration\Manager as Configuration;
use Bread\Storage\Exceptions;
use Exception;

class Driver
{

    protected static $drivers = array();

    protected static $mapping = array();

    public static function register($driver, $class)
    {
        if (is_string($driver)) {
            if (! isset(static::$drivers[$driver])) {
                static::$drivers[$driver] = static::factory($driver);
            }
            static::$mapping[$class] = static::$drivers[$driver];
        } else {
            static::$mapping[$class] = $driver;
        }
        return static::$mapping[$class];
    }

    public static function driver($class)
    {
        $classes = class_parents($class);
        array_unshift($classes, $class);
        foreach ($classes as $c) {
            if (isset(static::$mapping[$c])) {
                return static::$mapping[$c];
            } elseif ($url = Configuration::get($c, 'vendor.url')) {
                return static::register($url, $c);
            }
        }
        throw new Exceptions\DriverNotRegistered($class);
    }

    public static function factory($url)
    {
        $scheme = parse_url($url, PHP_URL_SCHEME);
        if (! $Driver = Configuration::get(__CLASS__, "drivers.$scheme")) {
            throw new Exception("Driver for {$scheme} not found.");
        }
        if (! is_subclass_of($Driver, 'Bread\Mail\Interfaces\Driver')) {
            throw new Exception("{$Driver} isn't a valid driver.");
        }
        return new $Driver($url);
    }
}

Configuration::defaults('Bread\Mail\Driver', array(
    'drivers' => array(
        'phpmailer' => 'Bread\Mail\Drivers\PHPMailer',
        'ezc' => 'Bread\Mail\Drivers\ZetaComponents',
    )
));
