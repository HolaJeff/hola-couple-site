<?php

namespace HW\UI\Forms\Traits;

use HW\UI\Forms\Controls\FormControl;

trait HasSchema {

    protected array $schema = [];

    /**
     * Set the schema controls for this field
     *
     * @param FormControl[] $controls
     * @return static
     */
    public function schema(array $controls): static {

        $this->schema = $controls;

        return $this;
    }

    /**
     * Get the schema controls
     *
     * @return FormControl[]
     */
    public function getSchema(): array {

        return $this->schema;
    }
}
