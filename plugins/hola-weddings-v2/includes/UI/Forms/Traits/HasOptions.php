<?php

namespace HW\UI\Forms\Traits;

trait HasOptions{

    protected array $options;

    /**
     * setter for options
     *
     * @param array $options
     * @return array
     */
    public function options(array $options ){

        $this->options = $options;

        return $this;
    }

    /**
     * getter for options
     *
     * @return array
     */
    public function getOptions(){

        return $this->options ?? [];
    }
}