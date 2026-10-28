<?php

namespace HW\UI\Forms\Layouts;

use HW\UI\Forms\FormComponent;
use HW\UI\Forms\Traits\HasColumns;
use HW\UI\Forms\Traits\HasSchema;

class Grid extends FormComponent {

    use HasSchema;
    use HasColumns;

    /**
     * constructor
     *
     */
    public function __construct(){
        
        $this->wrapperClass('hw-grid row g-3 mb-3');
    }

    /**
     * Render the grid layout and all its schema components
     *
     * @return void
     */
    public function render(): void {
        ?>
        <div <?php renderAttributes($this->getWrapperAttributes()) ?>>
            <?php 
            foreach ($this->getSchema() as $component): 
                $component
                    ->wrapperClass($component->getColClass($this->getColumns()))
                    ->render(); 
            endforeach; 
            ?>
        </div>
        <?php
    }
}
