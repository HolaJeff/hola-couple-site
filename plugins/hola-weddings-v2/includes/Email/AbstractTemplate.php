<?php

namespace HW\Email;

/**
 * Abstract base class for email services.
 * Subclasses define payload type via constructor and implement hooks.
 */
abstract class AbstractTemplate implements TemplateInterface {

    protected $layout = 'templates/emails/layout.php';

    /**
     * getter for layout
     *
     * @return string
     */
    public function getLayout(): string
    {
        return $this->layout;
    }

    /**
     * return template data
     *
     * @return array
     */
    public function getData(): array {
        return [ ];
    }

    /**
     * Headers for this message.
     *
     * Override to set a custom From / Reply-To. Returning an empty array leaves
     * the Email defaults in place.
     *
     * @return array
     */
    public function getHeaders(): array {
        return [ 'Content-Type: text/html; charset=UTF-8' ];
    }
}