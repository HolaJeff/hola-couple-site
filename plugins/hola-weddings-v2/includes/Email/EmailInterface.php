<?php

namespace HW\Email;

/**
 * Interface for email messages.
 */
interface EmailInterface {

    public function getRecipients(): array;

    public function getSubject(): string;

    public function getHeaders(): array;
    
    public function getBody(): string;
}