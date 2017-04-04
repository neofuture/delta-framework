<?php

namespace NeoFuture\Library;

class Response
{

    protected $headers;
    protected $content;
    protected $version = "1.0";
    protected $statusCode;
    protected $statusText;

    public function __construct($content = '', $status = 200, $headers = array())
    {

        $this->content = $content;
        $this->statusCode = $status;
        $this->statusText = Config::get("response.statusText." . $status);
        $this->headers = $headers;

    }

    public function __destruct()
    {
        $this->setHeaders();
    }

    public function headers($key, $value)
    {
        $this->headers[$key][] = $value;
        return $this;
    }

    public function json(){
        $this->headers["Content-type"][0] = "application/json";
        $this->setHeaders();
        return $this->content;
    }

    public function status($status)
    {
        $this->statusCode = $status;
        return $this;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function getStatus()
    {
        return $this->statusCode;
    }

    public function getHeaders()
    {
        return $this->headers;
    }


    public function setHeaders()
    {
        if(!headers_sent()){
            foreach ($this->getHeaders() as $name => $values) {
                foreach ($values as $value) {
                    header($name . ': ' . $value, false, $this->getStatus());
                }
            }
            header(sprintf('HTTP/%s %s %s', $this->version, $this->getStatus(), $this->statusText), true, $this->getStatus());
        }
    }

    public function __toString()
    {
        return $this->getContent();
    }


}