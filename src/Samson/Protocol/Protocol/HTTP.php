<?php

namespace Samson\Protocol\Protocol;

use Samson\Protocol\Socket;

class HTTP
{

    private $timeout = 5000;

    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
    }

    public function request($url, $method = 'GET"', $headers = array(), $content = '')
    {
        $urlData = parse_url($url);

        $socket = new Socket($this->timeout);
        $socket->connect($urlData['host'], 80);

        if (!isset($headers['Host'])) {
            $headers[] = 'Host: '.$urlData['host'];
        }
        if (strlen($content)) {
            $headers[] = 'Content-length: '.strlen($content);
        }

        $socket->writeLine(sprintf('%s %s HTTP/1.1', $method, $urlData['path'].(isset($urlData['query']) ? '?'.$urlData['query'] : '')));

        foreach ($headers as $header) {
            $socket->writeLine($header);
        }
        $socket->writeLine('');

        $socket->write($content);

        return $this->parseResponse($socket);
    }

    private function parseResponse(Socket $socket)
    {
        $response = $socket->readLine()."\r\n";

        $responseHeaders = array();
        while ($l = $socket->readLine()) {
            if (false === strpos($l, ": ")) {
                throw new Exception\InvalidHeaderException("Malformed header '".$l."', missing ': ' ");
            }
            list($key, $val) = explode(": ", $l);
            $responseHeaders[$key] = $val;
            $response .= $l."\r\n";
        }
        $response .= "\r\n";

        $content = $socket->readCleanBuffer();
        if (array_key_exists('Content-Length', $responseHeaders)) {
            while (strlen($content) < (int) $responseHeaders['Content-Length']) {
                $read = $socket->read(128);
                $content .= $read;
            }
        } else {
            while (false !== ($read = $socket->read(128))) {
                $content .= $read;
            }
        }

        return $response.$content;
    }
}