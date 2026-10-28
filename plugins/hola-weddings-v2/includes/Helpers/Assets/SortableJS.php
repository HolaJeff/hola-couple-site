<?php

namespace HW\Helpers\Assets;

class SortableJS {

    public static function import(): void {

        wp_enqueue_script(
            'sortablejs',
            'https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js',
            [],
            '1.15.6',
            true
        );
    }
}
