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
        
        // read the file
        ob_start();
        include $file;
        $template_html = ob_get_clean();

        // replace {{var}} or simple PHP tags, whichever you prefer
        foreach ($context as $k => $v) {

            $template_html = str_replace("{{{$k}}}", $v, $template_html);
        }

        return $template_html;
    }
}