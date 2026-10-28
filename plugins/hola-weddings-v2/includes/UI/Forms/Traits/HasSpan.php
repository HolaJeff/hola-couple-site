<?php

namespace HW\UI\Forms\Traits;

trait HasSpan {

    protected int $span = 1;

    /**
     * Set how many columns this component should occupy within a column layout.
     *
     * @param int $columns
     * @return static
     */
    public function span(int $columns): static {

        $this->span = $columns;

        return $this;
    }

    /**
     * Get the span value.
     *
     * @return int
     */
    public function getSpan(): int {

        return $this->span;
    }

    /**
     * Resolve the Bootstrap col class for this component given the total number
     * of columns in the parent layout.
     *
     * Examples (totalColumns = 3):
     *   span(1) → col-4   (4/12 = 1/3)
     *   span(2) → col-8   (8/12 = 2/3)
     *   span(3) → col-12  (full width)
     *
     * @param int $totalColumns  Total columns declared on the parent layout.
     * @return string
     */
    public function getColClass(int $totalColumns): string {

        if ($totalColumns <= 1) {
            return 'col-12';
        }

        $bs = (int) round(12 / $totalColumns * $this->span);

        return 'col-' . min(12, max(1, $bs));
    }
}
