<?php

namespace AppBundle\Helper;

class GenericHelper
{
    public static 
        $logger;

    public function __construct($logger)
    {
        self::$logger = $logger;
    }

    public function onKernelRequest($event)
    {
        return;
    }

    public function onConsoleCommand($event)
    {
        return;
    }

    public static function log($str, $level = 'notice')
    {
        if (!is_string($str)) {
            $str = print_r($str, TRUE);
        }

        $accepted_levels = array(
            'emergency',
            'alert',
            'critical',
            'warning',
            'notice',
            'info',
            'debug'
        );

        if (!in_array($level, $accepted_levels)) {
            $level = 'info';
        }

        self::$logger->$level($str);
    }

    public static function getLogger()
    {
        return self::$logger;
    }

    public static function snakify($word)
    {
        preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $word, $matches);
        $ret = $matches[0];
        foreach ($ret as &$match) {
            $match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
        }
        return implode('_', $ret);
    }

    public static function wordify($word)
    {
        $snakified = self::snakify($word);
        return str_replace('_', ' ', $snakified);
    }
}
