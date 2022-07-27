<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class WarcReader extends Controller
{

    ////////////////////////////////////////////////////////////////////////////////
    // 
    // Implemention from: https://github.com/philippelyp/php-warc/
    // Special thanks to: Philippe Paquet
    //
    ////////////////////////////////////////////////////////////////////////////////  

    //
    // Variables
    //

    private $handle;
    private $error;

    //
    // __construct
    //

    function __construct()
    {
        $this->handle = FALSE;
        $this->error = '';
    }

    //
    // close
    //

    function close()
    {
        if (FALSE === gzclose($this->handle)) {
            $this->error = 'Error closing file';
            return FALSE;
        } else {
            $this->error = '';
            $this->handle = FALSE;
            return TRUE;
        }
    }

    //
    // error
    //

    function error()
    {
        return $this->error;
    }

    //
    // open
    //

    function open($filepath)
    {
        $this->handle = gzopen($filepath, 'r');
        if (FALSE === $this->handle) {
            $this->error = 'Error opening file';
            return FALSE;
        } else {
            $this->error = '';
            return TRUE;
        }
    }

    //
    // read
    //

    function read()
    {
        if (FALSE !== $this->handle) {

            $header = array();

            $line = gzgets($this->handle);
            if (FALSE === $line) {
                $this->error = 'Read error';
                return FALSE;
            }

            while ("\r\n" == $line) {
                $line = gzgets($this->handle);
                if (FALSE === $line) {
                    $this->error = 'Read error';
                    return FALSE;
                }
            }

            while ($line != "\r\n") {
                $parts = explode(': ', $line, 2);
                switch (trim($parts[0])) {
                    case 'WARC/1.0':
                    case 'WARC/1.1':
                        $header['Version'] = trim($parts[0]);
                        break;
                    default:
                        $header[trim($parts[0])] = trim($parts[1]);
                        break;
                }
                $line = gzgets($this->handle);
                if (FALSE === $line) {
                    $this->error = 'Read error';
                    return FALSE;
                }
            }

            if (TRUE == array_key_exists('Content-Length', $header)) {

                $content = gzread($this->handle, $header['Content-Length']);
                if (FALSE === $content) {
                    $this->error = 'Read error';
                    return FALSE;
                } else {
                    $content = mb_convert_encoding($content, 'UTF-8');
                    if (FALSE === $content) {
                        $this->error = 'Error converting to UTF-8';
                        return FALSE;
                    }
                }

                $line = gzgets($this->handle);
                if (FALSE === $line) {
                    $this->error = 'Read error';
                    return FALSE;
                }

                $line = gzgets($this->handle);
                if (FALSE === $line) {
                    $this->error = 'Read error';
                    return FALSE;
                }

                return array('header' => $header, 'content' => $content);
            } else {

                $this->error = 'Content-Length missing from header';
                return FALSE;
            }
        } else {

            $this->error = 'File not open';
            return FALSE;
        }
    }
}
