<?php

namespace HW\UI\Forms\Layouts;

use HW\UI\Forms\FormComponent;
use HW\UI\Forms\Traits\HasColumns;
use HW\UI\Forms\Traits\HasSchema;

class Section extends FormComponent {

    use HasSchema;
    use HasColumns;

    protected string $title = '';
    protected string $description = '';

    /**
     * constructor
     *
     */
    public function __construct(){
        
        $this->wrapperClass('hw-section mb-3');
    }

    public function title(string $title): static {

        $this->title = $title;

        return $this;
    }

    public function description(string $description): static {

        $this->description = $description;

        return $this;
    }

    /**
     * Render the section as a card with header and body
     *
     * @return void
     */
    public function render(): void {
        ?>
        <div <?php renderAttributes($this->getWrapperAttributes()) ?>>
            <div class="inner card p-0">
                <?php if ($this->title || $this->description): ?>
                    <div class="card-header">
                        <?php if ($this->title): ?>
                            <h5 class="mb-0"><?= esc_html($this->title) ?></h5>
                        <?php endif; ?>
                        <?php if ($this->description): ?>
                            <p class="text-muted small mb-0 mt-1"><?= esc_html($this->description) ?></p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($this->getSchema() as $component): ?>
                            <?php 
                            $component
                                ->wrapperClass(['mb-3', $component->getColClass($this->getColumns())])
                                ->render(); 
                            ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}
