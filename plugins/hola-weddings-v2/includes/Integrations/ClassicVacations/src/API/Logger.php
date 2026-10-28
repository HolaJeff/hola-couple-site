<?php

namespace HW\Integrations\ClassicVacations\API;

use Exception;
use HW\Traits\Multiton;

/**
 * This class to initialize the SDK logger.
 */
class Logger{

    use Multiton;

    private static $logPrecedence = array("OFF", "FATAL", "ERROR", "WARNING", "INFO", "DEBUG", "TRACE", "ALL");

    private static $filePath = null;

    private static $level = 'DEBUG';

    /**
     * Initialize variables
     *
     * @param string $level
     * @return void
     */
    public static function initialize($level = '')
    {

        self::$filePath = CLASSICV_PATH . '/logs/logs.txt';
        
        if(!file_exists( CLASSICV_PATH . '/logs' )){

            mkdir(CLASSICV_PATH . '/logs', 0777, true);
        }
        
        if(!empty($level)){

            self::$level = $level;
        }
    }

    private static function checkLevel($messageLevel, $loggerLevel)
    {
        $message_index = array_search($messageLevel , self::$logPrecedence);

        $logger_index = array_search($loggerLevel, self::$logPrecedence);

        return ($message_index <= $logger_index) ? TRUE : FALSE;
    }

    public static function writeToFile($messageLevel, $msg)
    {
        
        if(self::$level == "OFF")
        {
            return;
        }

        $filePointer = fopen(self::$filePath, "a+");

        if (!$filePointer)
        {
            return;
        }
        
        if(self::$level == "ALL" || self::checkLevel($messageLevel, self::$level) == TRUE)
        {
            fwrite($filePointer, sprintf("%s %s %s %s\n", date("Y-m-d H:i:s"), "HW\Integrations\ClassicVacations\API\Logger", $messageLevel, $msg));

            fclose($filePointer);
        }
    }

    public static function warn($msg)
    {
        self::writeToFile("WARNING", $msg);
    }

    public static function info($msg)
    {
        self::writeToFile("INFO",  $msg);
    }

    public static function severe(Exception $e)
    {
        $message = self::parseException($e);

        self::writeToFile("SEVERE", $message);
    }

    public static function severeError($message, Exception $e=null)
    {
        $parsedMessage = $message;

        if($e != null)
        {
            $parsedMessage = $parsedMessage . " " . self::parseException($e);
        }

        self::writeToFile("SEVERE", $parsedMessage);
    }

    private static function parseException(Exception $e)
    {
        $message = "";

        $message = $message . $e->getFile() . "- " . $e->getLine() . "- " . $e->getMessage() . "\n";

        return $message . $e->getTraceAsString();
    }

    public static function err(Exception $e)
    {
        $message = self::parseException($e);

        self::writeToFile("ERROR", $message);
    }

    public static function error($message)
    {
        self::writeToFile("ERROR", $message);
    }

    public static function debug($msg)
    {
        self::writeToFile("DEBUG", $msg);
    }
}
