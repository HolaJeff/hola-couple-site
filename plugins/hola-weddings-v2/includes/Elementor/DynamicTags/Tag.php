<?php

namespace HW\Elementor\DynamicTags;

use HW\Elementor;

/**
 * Base class for the plugin's text dynamic tags.
 *
 * Places every tag under the "Hola Weddings" group in the dynamic tags panel so
 * subclasses only declare their name, title, categories and output.
 *
 * The advanced section (before / after / fallback) is registered automatically
 * by \Elementor\Core\DynamicTags\Tag, so tags get those controls for free.
 */
abstract class Tag extends \Elementor\Core\DynamicTags\Tag {

    public function get_group() {

        return Elementor::DYNAMIC_TAGS_GROUP;
    }
}
