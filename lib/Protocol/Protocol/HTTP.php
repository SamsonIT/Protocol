<?php

namespace Samson\Protocol\Protocol;

use Samson\Protocol\Socket;

class HTTP
{
    private $s;

    public function request($url, $method = 'GET"', $headers = array(), $content = '')
    {
        $urlData = parse_url($url);

        $this->s = new Socket();
        $this->s->connect($urlData['host'], 80);

        if (!isset($headers['Host'])) {
            $headers[] = 'Host: '.$urlData['host'];
        }
        if (strlen($content)) {
            $headers[] = 'Content-length: '.strlen($content);
        }

        $this->s->writeLine(sprintf('%s %s HTTP/1.1', $method, $urlData['path'].(isset($urlData['query']) ? '?'.$urlData['query'] : '')));

        foreach ($headers as $header) {
            $this->s->writeLine($header);
        }
        $this->s->writeLine('');

        $this->s->write($content);

        return $this->parseResponse();
    }

    private function parseResponse()
    {
        $response = $this->s->readLine()."\r\n";

        $responseHeaders = array();
        while ($l = $this->s->readLine()) {
            if (false === strpos($l, ": ")) {
                var_dump($l);
                die();
            }
            list($key, $val) = explode(": ", $l);
            $responseHeaders[$key] = $val;
            $response .= $l."\r\n";
        }
        $response .= "\r\n";

        $content = $this->s->readCleanBuffer();
        if (array_key_exists('Content-Length', $responseHeaders)) {
            while (strlen($content) < (int) $responseHeaders['Content-Length']) {
                $read = $this->s->read(128);
                $content .= $read;
            }
        } else {
            while (false !== ($read = $this->s->read(128))) {
                $content .= $read;
            }
        }

        return $response.$content;
    }
}