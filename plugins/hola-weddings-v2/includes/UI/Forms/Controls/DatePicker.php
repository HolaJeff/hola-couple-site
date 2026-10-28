<?php

namespace HW\UI\Forms\Controls;

class DatePicker extends Text {

    protected ?string $dateFormat = null;

    protected function getType(): string {
        return 'text';
    }

    public function format(string $format): static {

        $this->dateFormat = $format;

        return $this;
    }

    protected function renderControl(): void {

        $this->attributes = array_merge([
            'autocomplete' => 'off',
        ], $this->attributes);

        if ($this->dateFormat !== null) {
            $this->attributes['data-date-format'] = $this->dateFormat;
        }

        $this->arguments['class'][] = 'hw-datepicker';

        parent::renderControl();
    }
}
