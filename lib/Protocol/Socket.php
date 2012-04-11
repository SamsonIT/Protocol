<?php

namespace Samson\Protocol;

class Socket
{
    private $s;

    private $readBuffer = '';

    private $open = false;

    public function __construct()
    {
        $this->s = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
    }

    public function connect($host, $port)
    {
        socket_set_option($this->s, SOL_SOCKET, SO_SNDTIMEO, array('sec' => 1, 'usec' => 0));
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
        if ($this->open === true)
            $this->close();
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
        $line = null;
        while (false === ($pos = strpos($this->readBuffer, "\r\n"))) {
            if (false === $this->open)
                break;

            if (false === $this->read(128)) {
                $pos = strlen($this->readBuffer);
                $this->close();
                break;
            }
        }

        $line = substr($this->readBuffer, 0, $pos);
        $this->readBuffer = substr($this->readBuffer, $pos + 2);

        return $line;
    }

    public function readCleanBuffer()
    {
        $buffer = $this->readBuffer;
        $this->readBuffer = '';
        return $buffer;
    }

    public function read($len)
    {
        if (false === $this->open)
            return false;
        $read = socket_read($this->s, $len);
        if ("" === $read)
            return false;
        $this->readBuffer .= $read;
        return $read === false ? "" : $read;
    }
}