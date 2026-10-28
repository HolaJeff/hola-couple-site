<?php

use HW\UI\Forms\Controls\Editor;
use HW\UI\Forms\Controls\Text;
use HW\UI\Forms\Controls\TextArea;
use HW\UI\Forms\Layouts\Grid;
use HW\UI\Forms\Layouts\Heading;

$age = hw_get_option('hw_ages', []);

Grid::make()->columns(6)->schema([
    Text::make('hw_ages[infant_max]')
        ->label('Infant Max Age')
        ->input('number')
        ->attributes(['min' => '0'])
        ->value($age['infant_max'] ?? '1'),
    Text::make('hw_ages[children_min]')
        ->label('Children Min Age')
        ->input('number')
        ->attributes(['min' => '0'])
        ->value($age['children_min'] ?? '2'),
    Text::make('hw_ages[children_max]')
        ->label('Children Max Age')
        ->input('number')
        ->attributes(['min' => '0'])
        ->value($age['children_max'] ?? 12),
    Text::make('hw_ages[teen_min]')
        ->label('Teen Min Age')
        ->input('number')
        ->attributes(['min' => '0'])
        ->value($age['teen_min'] ?? 13),
    Text::make('hw_ages[teen_max]')
        ->label('Teen Max Age')
        ->input('number')
        ->attributes(['min' => '0'])
        ->value($age['teen_max'] ?? 17),
])->render();

Heading::make()
    ->heading('Rates Disclosure')
    ->render();

TextArea::make('hw_label_rates_disclosure')
    ->label('Rates Disclosure')
    ->value(hw_get_rates_disclosure_label())
    ->rows(5)
    ->render();

Heading::make()
    ->heading('Dress Code')
    ->render();

Editor::make('hw_dress_code')
    ->value(hw_get_dress_code())
    ->render();

Heading::make()
    ->heading('Cancellation policy')
    ->render();

Editor::make('hw_cancellation_policy')
    ->value(hw_get_option('hw_cancellation_policy', ''))
    ->render();

Heading::make()
    ->heading('Terms and conditions')
    ->render();

Editor::make('hw_terms')
    ->value(hw_get_option('hw_terms', ''))
    ->render();