<?php

namespace HW\UI\Forms\Controls;

use HW\UI\Forms\Traits\HasOptions;

class Select extends FormControl{

    use HasOptions;

    protected bool $is_multiple = false;

    /**
     * return the type
     *
     * @return string
     */
    protected function getType()
    {
        return 'select';
    }

    /**
     * setter for is multiple
     *
     * @return self
     */
    public function isMultiple(){

        $this->is_multiple = true;

        return $this;
    }

    /**
     * render the control
     *
     * @return void
     */
    protected function renderControl(){

        // cast to array, this solves both single and multiple values
        $value = (array)$this->getValue();
        ?>
        <select id="<?= $this->getId() ?>" class="form-select" name="<?= $this->getName() ?>" <?php renderAttributes($this->getAttributes()) ?>>
            <?php foreach($this->options as $key => $label): ?>
                <option value="<?= $key ?>" <?= in_array( $key, $value ) ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
        </select>
        <?php
    }
}