<?php

namespace HW\Email;

/**
 * Base email class implementing common logic.
 */
class Email implements EmailInterface {

    protected array $recipients = [];

    protected string $subject = '';

    protected string $body = '';

    protected array $headers = ['Content-Type: text/html; charset=UTF-8'];

    /************/
    /* Setters  */
    /********** */

    /**
     * set recipients
     *
     * @param array $recipients
     * @return self
     */
    public function setRecipients(array $recipients): self {

        $this->recipients = $recipients;

        return $this;
    }

    /**
     * set subject
     *
     * @param string $subject
     * @return self
     */
    public function setSubject(string $subject): self {

        $this->subject = $subject;

        return $this;
    }

    /**
     * set headers
     *
     * @param array $headers
     * @return self
     */
    public function setHeaders(array $headers): self {

        $this->headers = $headers;

        return $this;
    }

    /**
     * set headers
     *
     * @param array $headers
     * @return self
     */
    public function setBody(string $body): self {

        $this->body = $body;

        return $this;
    }


    /************/
    /* Getters  */
    /********** */

     /**
      * returns recipients
      *
      * @return array
      */
    public function getRecipients(): array {

        return $this->recipients;
    }

    /**
     * returns subjoect
     *
     * @return string
     */
    public function getSubject(): string {

        return $this->subject;
    }

    /**
     * return headers
     *
     * @return array
     */
    public function getHeaders(): array {

        return $this->headers;
    }

    /**
     * returns the body
     *
     * @return string
     */
    public function getBody(): string {

        return $this->body;
    }
}
