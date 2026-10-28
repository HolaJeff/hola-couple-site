<?php

namespace HW\UI\Forms;

use HW\UI\Forms\Traits\HasAttributes;
use HW\UI\Forms\Traits\HasSpan;
use HW\UI\Forms\Traits\HasWrapperAttributes;

abstract class FormComponent implements FormComponentInterface {

    use HasAttributes;
    use HasSpan;
    use HasWrapperAttributes;

    protected $arguments = [];

    /**
     * Set the id attribute on the component
     *
     * @param string $id
     * @return static
     */
    public function id(string $id): static {

        $this->attributes['id'] = $id;

        return $this;
    }

    /**
     * Render the component
     *
     * @return void
     */
    abstract public function render(): void;

    /**
     * Static factory — works for all subclasses regardless of constructor signature.
     * FormControl overrides this to enforce a required $name parameter.
     *
     * @param mixed ...$args
     * @return static
     */
    public static function make(mixed ...$args): static {

        $class = new \ReflectionClass(static::class);

        return $class->newInstanceArgs($args);
    }
}
