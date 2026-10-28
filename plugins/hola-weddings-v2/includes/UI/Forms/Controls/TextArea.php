<?php

namespace HW\UI\Forms\Controls;

class TextArea extends FormControl {

    protected string $placeholder = '';
    protected int $rows = 4;

    protected function getType() {
        return 'textarea';
    }

    public function placeholder(string $value): static {

        $this->placeholder = $value;

        return $this;
    }

    public function getPlaceholder(): string {

        return $this->placeholder;
    }

    public function rows(int $rows): static {

        $this->rows = $rows;

        return $this;
    }

    public function getRows(): int {

        return $this->rows;
    }

    protected function renderControl(): void {
        ?>
        <textarea
            class="form-control <?= implode(' ', $this->arguments['class'] ?? []) ?>"
            name="<?= $this->getName() ?>"
            id="<?= $this->getId() ?>"
            rows="<?= $this->getRows() ?>"
            placeholder="<?= $this->getPlaceholder() ?>"
            <?php renderAttributes($this->getAttributes()) ?>
        ><?= esc_textarea($this->getValue()) ?></textarea>
        <?php
    }
}
