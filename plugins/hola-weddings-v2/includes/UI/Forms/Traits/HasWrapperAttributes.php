<?php

namespace HW\UI\Forms\Traits;

trait HasWrapperAttributes {

    /**
     * Merge HTML attributes onto the component wrapper element
     *
     * @param array $attrs
     * @return static
     */
    public function wrapperAttributes(array $attrs): static {

        $this->arguments['wrapper_attributes'] = array_merge(
            $this->arguments['wrapper_attributes'] ?? [],
            $attrs
        );

        return $this;
    }

    /**
     * Return wrapper attributes
     *
     * @return array
     */
    public function getWrapperAttributes(): array {

        return $this->arguments['wrapper_attributes'] ?? [];
    }

    /**
     * Add one or more classes to the component wrapper element
     *
     * @param string|array $class
     * @return static
     */
    public function wrapperClass(string|array $class): static {

        $classes = is_array($class) ? implode(' ', $class) : $class;

        return $this->wrapperAttributes(['class' => implode( ' ', [$this->arguments['wrapper_attributes']['class'], $classes ] )]);
    }

    /**
     * Set an Alpine x-show expression on the component wrapper
     *
     * @param string $expression
     * @return static
     */
    public function show(string $expression): static {

        return $this->wrapperAttributes(['x-show' => $expression]);
    }
}
