<?php

namespace HW\UI\Forms\Controls;

class Checkbox extends FormControl{

    protected $input_value = 1;

    protected $input_label;

    protected $styled = false;

    /**
     * setter for input value
     *
     * @param mixed $value
     * @return void
     */
    public function setInputValue(mixed $value){
        
        $this->input_value = $value;

        return $this;
    }

    /**
     * setter for input value
     *
     * @param mixed $value
     * @return void
     */
    public function setInputLabel(string $value){
        
        $this->input_label = $value;

        return $this;
    }

    /**
     * utility function
     *
     * @return boolean
     */
    public function isChecked(){

        return $this->getValue() == true;
    }

    /**
     * setter for value function
     *
     * @param mixed $value
     * @return static::class
     */
    public function value(mixed $value){

        $this->value = (bool)$value;

        return $this;
    }

    /**
     * return the type
     *
     * @return string
     */
    protected function getType()
    {
        return 'checkbox';
    }

    /**
     * getter for input value
     *
     * @return string
     */
    public function getInputLabel(){

        return $this->input_label ?? $this->getLabel();
    }

    /**
     * display the checkbox as switch
     *
     * @return self;
     */
    public function styled(){

        $this->styled = true;

        return $this;
    }

    /**
     * render the control
     *
     * @return void
     */
    protected function renderControl(){
        ?>
            <div class="form-check mb-3 <?= $this->arguments['wrapper_class'] ?? '' ?> <?= $this->styled ? 'form-switch' : '' ?>" >
                <input 
                    class="<?= $this->styled ? 'form-check-input ms-0 float-none' : '' ?>" 
                    name="<?= $this->getName() ?>" 
                    type="checkbox" 
                    value="<?= $this->input_value ?>" 
                    id="<?= $this->getId() ?>-checkbox" 
                    <?= $this->isChecked() ? 'checked' : '' ?> 
                    <?= $this->styled ? 'role="switch"' : '' ?>
                    <?php renderAttributes($this->getAttributes()) ?>
                >
                <label class="form-check-label" for="<?= $this->getId() ?>-checkbox">
                    <?= $this->input_label ?>
                </label>
            </div>
        <?php
    }
}