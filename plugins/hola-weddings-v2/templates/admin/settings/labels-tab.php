<?php

use HW\UI\Forms\Controls\Checkbox;
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

Heading::make()
    ->heading('Extra booking-form checkboxes')
    ->render();

TextArea::make('hw_extra_checkboxes')
    ->label('One checkbox per line, shown after the Terms & Conditions check. Start a line with * to make it required. Answers are stored on the quote.')
    ->value(hw_get_option('hw_extra_checkboxes', ''))
    ->render();

Heading::make()
    ->heading('Travel protection decline')
    ->render();

TextArea::make('hw_insurance_decline_text')
    ->label('Shown as a required checkbox when a guest books without a travel protection plan, and stored with a timestamp. Leave empty to switch it off. Have the wording approved by your licensed insurance professional. Example: I have been offered travel protection and I decline it. I understand my trip may be non-refundable.')
    ->value(hw_get_option('hw_insurance_decline_text', ''))
    ->render();

Heading::make()
    ->heading('Guest preferences')
    ->render();

TextArea::make('hw_bed_preferences')
    ->label('Bed preference choices, one per line (for example: One king bed / Two double beds). Leave empty to not ask. The choice reaches the CRM with the special requests, as a request and not a guarantee.')
    ->value(hw_get_option('hw_bed_preferences', ''))
    ->render();

Checkbox::make('hw_transfer_optional')
    ->id('transfer-optional')
    ->label('Let guests choose whether to add airport transfers (only when transfers are enabled with a price). Unticked by the guest = no transfer charge on the booking.')
    ->setInputLabel('Yes')
    ->value(hw_get_option('hw_transfer_optional', 0))
    ->styled()
    ->render();
