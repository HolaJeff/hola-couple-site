<?php

namespace HW\UI\Forms\Controls;

class Number extends Text {

    protected $inputType = 'number';

    protected function getType()
    {
        return 'number';
    }

    /**
     * Set the min attribute
     *
     * @param int|float $value
     * @return static
     */
    public function min(int|float $value): static {

        $this->attributes['min'] = $value;

        return $this;
    }

    /**
     * Set the max attribute
     *
     * @param int|float $value
     * @return static
     */
    public function max(int|float $value): static {

        $this->attributes['max'] = $value;

        return $this;
    }
}
