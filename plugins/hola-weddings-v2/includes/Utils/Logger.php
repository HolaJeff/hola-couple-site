<?php

namespace HW\Utils;

/**
 * Simple logger class.
 *
 * Log entries can be added with any of the following methods:
 *  - Logger::info( $message, $title = '' )      // an informational message intended for the user
 *  - Logger::debug( $message, $title = '' )     // a diagnostic message intended for the developer
 *  - Logger::warning( $message, $title = '' )   // a warning that something might go wrong
 *  - Logger::error( $message, $title = '' )     // explain why the program is going to crash
 *
 * See README.md for examples and configuration.
 */
class Logger {

    public static $debug = 'debug';

    public static $info = 'info';

    public static $warning = 'warning';

    public static $error = 'error';

    /**
     * Incremental log, where each entry is an array with the following elements:
     *
     *  - timestamp => timestamp in seconds as returned by time()
     *  - level => severity of the bug; one between debug, info, warning, error
     *  - name => name of the log entry, optional
     *  - message => actual log message
     */
    protected $log = [];

    /**
     * Whether to print log entries to screen as they are added.
     */
    public $print_log = false;

    /**
     * Whether to write log entries to file as they are added.
     */
    public  $write_log = true;

    /**
     * Directory where the log will be dumped, without final slash; default
     * is this file's directory
     */
    public  $log_dir = __DIR__;
    
    /**
     * File name for the log saved in the log dir
     */
    public  $log_file_name = "log";

    /**
     * File extension for the logs saved in the log dir
     */
    public  $log_file_extension = "txt";
    
    /**
     * Whether to append to the log file (true) or to overwrite it (false)
     */
    public  $log_file_append = true;

    /**
     * Set the maximum level of logging to write to logs
     */
    public  $log_level = 'error';

    /**
     * Name for the default timer
     */
    public  $default_timer = 'timer';

    /**
     * Map logging levels to syslog specifications, there's room for the other levels
     */
    private  $log_level_integers = [
        'debug' => 7,
        'info' => 6,
        'warning' => 4,
        'error' => 3
    ];

    /**
     * Absolute path of the log file, built at run time
     */
    private  $log_file_path = '';

    /**
     * Where should we write/print the output to? Built at run time
     */
    private  $output_streams = [];

    /**
     * Whether the init() function has already been called
     */
    private  $logger_ready = false;
    
    /**
     * Associative array used as a buffer to keep track of timed logs
     */
    private  $time_tracking = [];

    /**
     * Constructor
     *
     * @param string $dir
     * @param string $filename
     * @param string $ext
     */
    public function __construct($dir, $filename = 'log', $ext = 'txt', $write_log = true, $print_log = false)
    {

        // set file path
        $this->log_dir = $dir;
        $this->log_file_name = $filename;
        $this->log_file_extension = $ext;
        $this->print_log = $print_log;
        $this->write_log = $write_log;

        // init the logger
        $this->init();
    }

    /**
     * Add a log entry with a diagnostic message for the developer.
     */
    public function debug( $message, $name = '' ) {
        return $this->add( $message, $name, 'debug' );
    }


    /**
     * Add a log entry with an informational message for the user.
     */
    public function info( $message, $name = '' ) {
        return $this->add( $message, $name, 'info' );
    }


    /**
     * Add a log entry with a warning message.
     */
    public function warning( $message, $name = '' ) {
        return $this->add( $message, $name, 'warning' );
    }


    /**
     * Add a log entry with an error - usually followed by
     * script termination.
     */
    public function error( $message, $name = '' ) {
        return $this->add( $message, $name, 'error' );
    }


    /**
     * Start counting time, using $name as identifier.
     *
     * Returns the start time or false if a time tracker with the same name
     * exists
     */
    public function time( string $name = null ) {

        if ( $name === null ) {
            $name = $this->default_timer;
        }

        if ( ! isset( $this->time_tracking[ $name ] ) ) {
            $this->time_tracking[ $name ] = microtime( true );
            return $this->time_tracking[ $name ];
        }
        else {
            return false;
        }
    }


    /**
     * Stop counting time, and create a log entry reporting the elapsed amount of
     * time.
     *
     * Returns the total time elapsed for the given time-tracker, or false if the
     * time tracker is not found.
     */
    public function timeEnd( string $name = null, int $decimals = 6, $level = 'debug' ) {

        $is_default_timer = $name === null;

        if ( $is_default_timer ) {
            $name = $this->default_timer;
        }

        if ( isset( $this->time_tracking[ $name ] ) ) {
            $start = $this->time_tracking[ $name ];
            $end = microtime( true );
            $elapsed_time = number_format( ( $end - $start), $decimals );
            unset( $this->time_tracking[ $name ] );
            if ( ! $is_default_timer ) {
                $this->add( "$elapsed_time seconds", "Elapsed time for '$name'", $level );
            }
            else {
                $this->add( "$elapsed_time seconds", "Elapsed time", $level );
            }
            return $elapsed_time;
        }
        else {
            return false;
        }
    }


    /**
     * Add an entry to the log.
     *
     * This function does not update the pretty log. 
     */
    public function add( $message, $name = '', $level = 'debug' ) {

        /* Check if the logging level severity warrants writing this log */
        if ( $this->log_level_integers[$level] > $this->log_level_integers[$this->log_level] ){
            return;
        }

        /* Create the log entry */
        $log_entry = [
            'timestamp' => time(),
            'name' => $name,
            'message' => $message,
            'level' => $level,
        ];

        /* Add the log entry to the incremental log */
        $this->log[] = $log_entry;

        /* Write the log to output, if requested */
        if ( $this->logger_ready && count( $this->output_streams ) > 0 ) {
            $output_line = $this->format_log_entry( $log_entry ) . PHP_EOL;
            foreach ( $this->output_streams as $key => $stream ) {
                if ( is_resource( $stream ) ) {
                    fputs( $stream, $output_line );
                }
            }
        }

        return $log_entry;
    }


    /**
     * Take one log entry and return a one-line human readable string
     */
    public function format_log_entry( array $log_entry ) : string {

        $log_line = "";

        if ( ! empty( $log_entry ) ) {

            /* Make sure the log entry is stringified */
            $log_entry = array_map( function( $v ) { return print_r( $v, true ); }, $log_entry );
        
            /* Build a line of the pretty log */
            $log_line .= date( 'c', $log_entry['timestamp'] ) . " ";
            $log_line .= "[" . strtoupper( $log_entry['level'] ) . "] : ";
            if ( ! empty( $log_entry['name'] ) ) {
                $log_line .= $log_entry['name'] . " => ";
            }
            $log_line .= $log_entry['message'];
        
        }
        
        return $log_line;
    }
    
    
    /**
     * Determine whether an where the log needs to be written; executed only
     * once.
     *
     * @return {array} - An associative array with the output streams. The 
     * keys are 'output' for STDOUT and the filename for file streams.
     */
    public function init() {

        if ( ! $this->logger_ready ) {

            /* Print to screen */
            if ( true === $this->print_log ) {
                $this->output_streams[ 'stdout' ] = STDOUT;
            }

            /* Build log file path */
            if ( !file_exists( $this->log_dir ) ) {
                mkdir($this->log_dir, 0777, true);
            }
            
            if ( file_exists( $this->log_dir ) ) {
                $this->log_file_path = implode( DIRECTORY_SEPARATOR, [ $this->log_dir, $this->log_file_name ] );
                if ( ! empty( $this->log_file_extension ) ) {
                    $this->log_file_path .= "." . $this->log_file_extension;
                }
            }

            /* Print to log file. fopen can fail (unwritable dir on a fresh
               subsite) — a logger must never take the request down with it,
               so only register streams that actually opened. */
            if ( true === $this->write_log ) {
                if ( file_exists( $this->log_dir ) ) {
                    $mode = $this->log_file_append ? "a+" : "w";
                    $handle = @fopen( $this->log_file_path, $mode );
                    if ( is_resource( $handle ) ) {
                        $this->output_streams[ $this->log_file_path ] = $handle;
                    } else {
                        error_log( '[HW-LOGGER] could not open log file ' . $this->log_file_path . ' — file logging disabled for this request' );
                    }
                }
            }
        }

        /* Now that we have assigned the output stream, this function does not need
        to be called anymore */
        $this->logger_ready = true;

    }


    /**
     * Dump the whole log to the given file.
     *
     * Useful if you don't know before-hand the name of the log file. Otherwise,
     * you should use the real-time logging option, that is, the $write_log or
     * $print_log options.
     *
     * The method format_log_entry() is used to format the log.
     *
     * @param {string} $file_path - Absolute path of the output file. If empty,
     * will use the class property $log_file_path.
     */
    public function dump_to_file( $file_path='' ) {

        if ( ! $file_path ) {
            $file_path = $this->log_file_path;
        }
        
        if ( file_exists( dirname( $file_path ) ) ) {

            $mode = $this->log_file_append ? "a" : "w";
            $output_file = @fopen( $file_path, $mode );
            if ( ! is_resource( $output_file ) ) {
                error_log( '[HW-LOGGER] could not open ' . $file_path . ' for dump — skipped' );
                return;
            }

            foreach ( $this->log as $log_entry ) {
                $log_line = $this->format_log_entry( $log_entry );
                fwrite( $output_file, $log_line . PHP_EOL );
            }

            fclose( $output_file );
        }
    }
    
    
    /**
     * Dump the whole log to string, and return it.
     *
     * The method format_log_entry() is used to format the log.
     */
    public function dump_to_string() {
      
        $output = '';
        
        foreach ( $this->log as $log_entry ) {
            $log_line = $this->format_log_entry( $log_entry );
            $output .= $log_line . PHP_EOL;
        }
        
        return $output;
    }

    /**
     * Empty the log
     */
    public function clear_log() {
        $this->log = [];
    }

    /**
     * setter for the log level
     *
     * @param string $level
     * @return self
     */
    public function setLevel( string $level ){

        $allowed_values = array_keys($this->log_level_integers);

        if(in_array($level, $allowed_values)){
            
            $this->log_level = $level;
        }

        return $this;
    }

}