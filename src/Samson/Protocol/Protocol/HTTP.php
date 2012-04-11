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
        $protocol = 'HTTP/1.1';

        $urlData = parse_url($url);

        $socket = new Socket($this->timeout);

        $port = 80;
        if (isset($urlData['port'])) {
            $port = $urlData['port'];
        }
        $socket->connect($urlData['host'], $port);

        if (!isset($headers['Host'])) {
            $headers[] = 'Host: '.$urlData['host'];
        }
        if (strlen($content)) {
            $headers[] = 'Content-length: '.strlen($content);
        }

        $socket->writeLine(sprintf('%s %s %s', $method, $urlData['path'].(isset($urlData['query']) ? '?'.$urlData['query'] : ''), $protocol));

        foreach ($headers as $header) {
            $socket->writeLine($header);
        }
        $socket->writeLine('');

        $socket->write($content);

        return $this->parseResponse($socket, $protocol);
    }

    private function parseResponse(Socket $socket, $protocol)
    {
        $response = $socket->readLine();

        $answerParts = explode(" ", $response, 3);

        if ($answerParts[0] != $protocol) {
            throw new Exception\InvalidHeaderException("Unexpected protocol answer. Expected ".$protocol.", got ".$answerParts[0]);
        }
        if (!is_numeric($answerParts[1])) {
            throw new Exception\InvalidHeaderException("The response status code should be numeric, got ".$answerParts[1]);
        }

        $response .= "\r\n";

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

        $content = '';
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