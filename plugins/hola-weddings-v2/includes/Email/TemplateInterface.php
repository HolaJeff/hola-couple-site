<?php

namespace HW\Email;

/**
 * Interface for email templates.
 */
interface TemplateInterface {

    /**
     * getter for subjet
     *
     * @return string
     */
    public function getSubject(): string;

    /**
     * template to display
     *
     * @return string
     */
    public function getTemplate(): string;

    /**
     * data to pass to templare renderer
     *
     * @return array
     */
    public function getData(): array;

    /**
     * layout to pass to template rendered
     *
     * @return string
     */
    public function getLayout(): string;

    /**
     * recipients for this template
     *
     * @return array
     */
    public function getRecipients(): array;
}