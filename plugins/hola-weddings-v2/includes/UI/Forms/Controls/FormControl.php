<?php

namespace HW\UI\Forms\Controls;

use HW\UI\Forms\FormComponent;

abstract class FormControl extends FormComponent {

    protected $id;

    protected $name;

    protected $type;

    protected $label;

    protected $value;

    protected $description;

    /**
     * return the control type
     *
     * @return string
     */
    abstract protected function getType();

    /**
     * render content of the control
     *
     * @return void
     */
    abstract protected function renderControl();

    /**
     * constructor
     *
     * @param string $name
     */
    public function __construct(string $name){
        
        $this->name = $name;
        $this->id = $name;
        $this->type = $this->getType();
        $this->wrapperClass('form-group');
    }

    /**
     * setter for value
     *
     * @param mixed $value
     * @return static::class
     */
    public function value(mixed $value){

        $this->value = $value;

        return $this;
    }

    /**
     * Override to keep $this->id in sync with the attribute,
     * so label for="" and wrapper id="" stay consistent.
     *
     * @param string $id
     * @return static
     */
    public function id(string $id): static {

        $this->id = $id;

        return parent::id($id);
    }

    /**
     * setter for label
     *
     * @param string $label
     * @return static::class
     */
    public function label(string $label){

        $this->label = $label;

        return $this;
    }

    /**
     * set control arguments
     *
     * @param array $attrs
     * @return static::class
     */
    public function arguments($attrs = []){

        $this->arguments = array_merge($this->arguments, $attrs);

        return $this;
    }

    /**
     * setter for description
     *
     * @param string $value
     * @return static::class
     */
    public function description(string $value){

        $this->description = $value;

        return $this;
    }

    /**
     * getter for label
     *
     * @return string|null
     */
    public function getLabel(){

        return $this->label;
    }

    /**
     * getter for name
     *
     * @return string|null
     */
    public function getName(){

        return $this->name;
    }

    /**
     * getter for id
     *
     * @return string
     */
    public function getId(){

        return $this->id;
    }

    /**
     * getter for value
     *
     * @return mixed
     */
    public function getValue(){

        return $this->value;
    }

    /**
     * getter for description
     *
     * @return string
     */
    public function getDescription(){

        return $this->description;
    }

    /**
     * open control
     *
     * @return void
     */
    protected function open(){
        ?>
        <div id="<?= $this->getId() ?>-wrapper" <?php renderAttributes($this->arguments['wrapper_attributes'] ?? []) ?>>
            <?php if(!empty($this->getLabel())): ?>
                <label for="<?= $this->getId() ?>" class="form-label">
                    <?= $this->getLabel() ?>
                </label>
            <?php endif; ?>
        <?php
    }

    /**
     * close controls
     *
     * @return void
     */
    protected function close(){
            
            if(!empty($this->getDescription())): ?>
                <small class="form-text d-block">
                    <?= $this->getDescription() ?>
                </small>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * render the control
     *
     * @return void
     */
    public function render(): void{
        $this->open();
        $this->renderControl();
        $this->close();
    }

    /**
     * Static factory — requires a field name for all form controls.
     *
     * @param string $name
     * @return static
     */
    public static function make(mixed ...$args): static {

        if (empty($args[0]) || !is_string($args[0])) {
            throw new \InvalidArgumentException(
                static::class . '::make() requires a field name as first argument.'
            );
        }

        $class = new \ReflectionClass(static::class);

        return $class->newInstance($args[0]);
    }
}