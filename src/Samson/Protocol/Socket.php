<?php

namespace Samson\Protocol;

class Socket
{
    private $s;

    private $readBuffer = '';

    private $open = false;

    public function __construct($timeout = 5000)
    {
        $this->s = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_set_option($this->s, SOL_SOCKET, SO_SNDTIMEO, array('sec' => floor($timeout / 1000), 'usec' => $timeout % 1000));
    }

    public function connect($host, $port)
    {
        try {
            if (false === socket_connect($this->s, $host, $port)) {
                throw new SocketException('Connection error ('.socket_last_error($this->s).'): '.socket_strerror(socket_last_error($this->s)));
            }
        } catch (Exception $e) {
            throw new SocketException($e->getMessage());
        }
        socket_set_nonblock($this->s);
        $this->open = true;
    }

    public function close()
    {
        socket_close($this->s);
        $this->open = false;
    }

    public function __destruct()
    {
        if ($this->open === true) {
            $this->close();
        }
    }

    public function writeLine($line)
    {
        $this->write($line."\r\n");
        return $this;
    }

    function write($msg)
    {
        socket_write($this->s, $msg);
        return $this;
    }

    public function readLine()
    {
        $line = false;
        while ($this->open && false === ($pos = strpos($this->readBuffer, "\r\n"))) {
            if (false === $this->readSocket(128)) {
                if ("" === $this->readBuffer) {
                    return false;
                }
                $pos = strlen($this->readBuffer);
                break;
            }
        }

        $line = substr($this->readBuffer, 0, $pos);
        $this->readBuffer = substr($this->readBuffer, $pos + 2);

        return $line;
    }

    public function read($len)
    {
        $return = substr($this->readBuffer, 0, $len);
        if (strlen($return) < $len) {
            if (false !== $this->readSocket($len - strlen($return))) {
                $return = substr($this->readBuffer, 0, $len);
            }
        }

        $this->readBuffer = substr($this->readBuffer, strlen($return));

        return $return;
    }

    private function readSocket($len)
    {
        if (false === $this->open) {
            return false;
        }
        $read = socket_read($this->s, $len);

        if (false === $read) {
            if (socket_last_error($this->s) == 11) { // Nothing to read (yet)
                return true;
            } else { // Some error apparently happened
                return false;
            }
        }

        if (false !== $read) {
            $this->readBuffer .= $read;
        }
        return true;
    }
}