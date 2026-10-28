<?php

namespace HW\UI\Forms\Traits;

trait HasAttributes {

    protected array $attributes = [];

    /**
     * Set HTML attributes on the component
     *
     * @param array $attrs
     * @return static
     */
    public function attributes(array $attrs = []): static {

        $this->attributes = array_merge($this->attributes, $attrs);

        return $this;
    }

    /**
     * Get HTML attributes
     *
     * @return array
     */
    public function getAttributes(): array {

        return $this->attributes;
    }
}
