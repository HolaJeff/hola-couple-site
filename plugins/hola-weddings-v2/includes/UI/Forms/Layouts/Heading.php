<?php

namespace HW\UI\Forms\Layouts;

use HW\UI\Forms\FormComponent;

class Heading extends FormComponent {

    protected string $heading = '';
    protected string $subHeading = '';
    protected string $tag = 'h3';

    public function heading(string $heading): static {

        $this->heading = $heading;

        return $this;
    }

    public function subHeading(string $subHeading): static {

        $this->subHeading = $subHeading;

        return $this;
    }

    public function tag(string $tag): static {

        $this->tag = $tag;

        return $this;
    }

    public function render(): void {
        ?>
        <div class="hw-heading border-top pt-3 mt-3 mb-3">
            <?php if ($this->heading): ?>
                <<?= tag_escape($this->tag) ?>><?= esc_html($this->heading) ?></<?= tag_escape($this->tag) ?>>
            <?php endif; ?>
            <?php if ($this->subHeading): ?>
                <p class="text-muted"><?= esc_html($this->subHeading) ?></p>
            <?php endif; ?>
        </div>
        <?php
    }
}
