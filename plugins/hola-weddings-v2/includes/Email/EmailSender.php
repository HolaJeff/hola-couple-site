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

        $mailer  = hw_get_mailer();
        $subject = $template->getSubject();
        $html    = $this->render( $template );

        // Send
        $email = (new Email())
            ->setRecipients($template->getRecipients())
            ->setSubject($subject)
            ->setBody($html);

        // Optional: templates that need their own From/Reply-To can declare
        // getHeaders(). Not on TemplateInterface, so templates written against
        // the interface alone keep working with the Email defaults.
        if ( method_exists( $template, 'getHeaders' ) ) {

            $headers = $template->getHeaders();

            if ( ! empty( $headers ) ) {
                $email->setHeaders( $headers );
            }
        }

        return $mailer->send($email);
    }

    /**
     * Render a template to its final HTML, without sending anything.
     *
     * Split out of send() so the message body can be inspected on its own —
     * that is what the WP-CLI preview commands print.
     *
     * @param TemplateInterface $template
     * @return string
     */
    public function render( TemplateInterface $template ): string {

        $innerHtml = $this->renderer->render( $template->getTemplate(), $template->getData() );

        $layout = $template->getLayout();

        // An empty layout means the template is already a complete document, or
        // deliberately wants raw markup with no branded chrome around it.
        if ( $layout === '' ) {

            return $innerHtml;
        }

        return $this->renderer->render( $layout, [
            'subject' => $template->getSubject(),
            'content' => $innerHtml,
        ] );
    }
}