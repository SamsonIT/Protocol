<?php

namespace Samson\Protocol\Protocol;

class HTTP
{
    private $s;

    private $responseHeaders;

    private $response;

    public function __construct($url, $method = 'GET"', $headers = array(), $content = null)
    {
        $this->s = new Samson\Protocol\Socket();
        $this->s->connect($url, 80);

        $urlData = parse_url($url);

        if (!isset($headers['Host'])) {
            $headers['Host'] = $urlData['host'];
        }
        if (strlen($content)) {
            $headers['Content-length'] = strlen($content);
        }
        
        $this->s->writeLine(sprintf('%s %s HTTP/1.1', $method, $urlData['path'].(isset($urlData['query']) ? '?'.$urlData['query'] : '')));
            
        foreach($headers as $key => $value) {
            $s->writeln(sprintf("%s: %s", $key, $value));
        }
        $s->writeln('');
        
        if (strlen($content)) {
            $this->s->write($content);
        }
    }

    private function parseResponse()
    {

        $responseHeaders = array();
        $answer = $this->s->readLine();
        while ($l = $this->s->readLine()) {
            if (false === strpos($l, ": ")) {
                var_dump($l);
                die();
            }
            list($key, $val) = explode(": ", $l);
            $responseHeaders[$key] = $val;
        }

        $this->responseHeaders = $responseHeaders;

        $response = $this->s->readCleanBuffer();
        if (array_key_exists('Content-Length', $responseHeaders)) {
            while (strlen($response) < (int) $responseHeaders['Content-Length']) {
                $read = $this->s->read(128);
                $response .= $read;
            }
        } else {
            while (false !== ($read = $this->s->read(128))) {
                $response .= $read;
            }
        }

        $this->response = $response;
    }

    public function getResponseHeaders()
    {
        if (null === $this->responseHeaders) {
            $this->parseResponse();
        }
        return $this->responseHeaders;
    }

    public function getResponse()
    {
        if (null === $this->response) {
            $this->parseResponse();
        }
        return $this->response;
    }
}