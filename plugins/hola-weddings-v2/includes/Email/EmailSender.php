<?php

namespace HW\Email;

/**
 * base class for email services.
 * Subclasses define payload type via constructor and implement hooks.
 */
class EmailSender {

    protected TemplateRenderer $renderer;

    public function __construct() {
        
        $this->renderer = new TemplateRenderer();
    }

    /**
     * send email
     *
     * @param TemplateInterface $template
     * @return boolean|\WP_Error
     */
    public function send( TemplateInterface $template ): bool|\WP_Error {

        $mailer = hw_get_mailer();
        $subject    = $template->getSubject();
        $templatePath   = $template->getTemplate();
        $data       = $template->getData();

        // Render inner content and wrap in layout
        $innerHtml  = $this->renderer->render($templatePath, $data);
        
        $layoutVars = array_merge(
            [
                'subject' => $subject, 
                'content' => $innerHtml
            ],
            []
        );
        
        $html = $this->renderer->render($template->getLayout(), $layoutVars);

        // Send
        $email = (new Email())
            ->setRecipients($template->getRecipients())
            ->setSubject($subject)
            ->setBody($html);

        return $mailer->send($email);
    }
}