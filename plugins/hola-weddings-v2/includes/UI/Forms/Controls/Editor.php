<?php

namespace HW\UI\Forms\Controls;

class Editor extends FormControl{

    protected function getType()
    {
        return 'editor';
    }

    protected function renderControl()
    {
        $default_editor_args = [
            '_content_editor_dfw' => true,
            'drag_drop_upload'    => true,
            'tabfocus_elements'   => 'content-html,save-post',
            'editor_height'       => 300,
            'tinymce'             => array(
                'resize'                  => false,
                'wp_autoresize_on'        => true,
                'add_unload_trigger'      => false,
            ),
        ];

        $editor_args = wp_parse_args($this->arguments['editor_args'] ?? [], $default_editor_args);

        wp_editor(
            $this->getValue(),
            $this->getName(),
            $editor_args
        );
    }
}