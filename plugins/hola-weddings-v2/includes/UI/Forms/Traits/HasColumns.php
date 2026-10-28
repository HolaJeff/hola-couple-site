<?php

namespace HW\UI\Forms\Traits;

trait HasColumns {

    protected int $columns = 1;

    /**
     * Set the number of columns
     *
     * @param int $columns
     * @return static
     */
    public function columns(int $columns): static {

        $this->columns = $columns;

        return $this;
    }

    /**
     * Get the number of columns
     *
     * @return int
     */
    public function getColumns(): int {

        return $this->columns;
    }
}
