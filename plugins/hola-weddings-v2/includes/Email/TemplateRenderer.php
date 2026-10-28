<?php

namespace HW\Email;

use RuntimeException;

/**
 * Simple renderer for PHP-based templates.
 */
class TemplateRenderer {

    /**
     * render the template
     *
     * @param string $template
     * @param array $context
     * @return string
     */
    public function render(string $templatePath, array $context = []): string {

        $file = HW_PLUGIN_PATH . '/' . ltrim($templatePath, '/');
        
        if (!file_exists($file)) {
            
            throw new RuntimeException("Email template file {$file} not found.");
        }
        
        /*
         * Both styles the templates use are supported here:
         *
         *   <?= esc_html($subject) ?>   PHP variables, extracted below
         *   {{subject}}                 substituted after rendering
         *
         * The context was never extracted, so the first style silently rendered
         * nothing — which is why the layout's <title> came out empty even though
         * the subject was set correctly on the message.
         *
         * Rendered inside a closure so the extracted names cannot collide with
         * this method's own locals ($file, $context, $template_html).
         */
        $template_html = ( static function ( $hw_template_file, $hw_template_context ) {

            extract( $hw_template_context, EXTR_SKIP );

            ob_start();
            include $hw_template_file;

            return ob_get_clean();

        } )( $file, $context );

        foreach ($context as $k => $v) {

            // Only scalars can be substituted into markup; anything else would
            // raise a conversion error rather than fail quietly.
            if ( ! is_scalar( $v ) && ! is_null( $v ) ) {
                continue;
            }

            $template_html = str_replace("{{{$k}}}", (string) $v, $template_html);
        }

        return $template_html;
    }
}