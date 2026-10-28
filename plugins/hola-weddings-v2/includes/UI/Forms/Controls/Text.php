<?php

namespace HW\UI\Forms\Controls;

class Text extends FormControl{

    protected $placeholder = '';

    protected $inputType = 'text';

    protected function getType()
    {
        return 'text';
    }

    /**
     * set input type
     *
     * @param string $type
     * @return static
     */
    public function input(string $type): static {

        $this->inputType = $type;

        return $this;
    }

    /**
     * return input type
     *
     * @return string
     */
    public function getInput(): string {

        return $this->inputType;
    }

    /**
     * setter for placeholder
     *
     * @param string $value
     * @return self
     */
    public function placeholder( string $value ): self{

        $this->placeholder = $value;

        return $this;
    }

    /**
     * getter for placeholder
     *
     * @return string
     */
    public function getPlaceholder(): string{

        return $this->placeholder;
    }

    /**
     * render text control
     *
     * @return void
     */
    protected function renderControl(): void
    {
        ?>
        <input 
            class="form-control <?= implode(' ', $this->arguments['class'] ?? [] ) ?>" 
            name="<?= $this->getName() ?>" 
            type="<?= $this->getInput() ?>"
            value="<?= $this->getValue() ?>" 
            id="<?= $this->getId() ?>"
            placeholder="<?= $this->getPlaceholder() ?>"
            <?php renderAttributes($this->getAttributes()) ?>
        >
        <?php
    }
}