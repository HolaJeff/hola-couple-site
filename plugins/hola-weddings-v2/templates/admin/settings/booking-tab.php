<?php

use HW\UI\Forms\Controls\Checkbox;
use HW\UI\Forms\Controls\Select;
use HW\UI\Forms\Controls\Text;
use HW\UI\Forms\Controls\Number;
use HW\UI\Forms\Layouts\Grid;
use HW\UI\Forms\Layouts\Heading;
use HW\API\Operations\Insurances;
use HW\UI\Forms\Controls\TextArea;

$insurances = Insurances::getList();
$insurances = array_merge(['0' => '- Select Insurance -'], $insurances);

Grid::make()
    ->columns(2)
    ->schema([
        Text::make('hw_alternative_email')
            ->label('Alternative email addresses')
            ->description(__('Separated by comma', 'hola-weddings'))
            ->value(hw_get_option('hw_alternative_email', '')),
        Number::make('hw_min_nights')
            ->label('Minimum Nights')
            ->min(0)
            ->value(hw_get_option('hw_min_nights'))
    ])
    ->render();

Heading::make()
    ->heading('Insurance')
    ->render();

if( is_wp_error( $insurances ) ): ?>
	<p class="alert alert-warning"><?= __('An error ocurred while retrieving the insurance list from the API.', 'hola-weddings') ?></p>
<?php else: ?>
	<?php Select::make('hw_insurance')
		->label('Insurance Plan')
		->value(hw_get_insurance_id())
		->options($insurances)
		->render();
	?>
<?php endif;

// Person Types

Heading::make()
    ->heading('Person Types')
    ->subHeading('Configure person types.')
    ->render();

Checkbox::make('hw_enable_2_child')
    ->id('enable-2-child')
    ->label('Enable 2 Child')
    ->setInputLabel('Yes')
    ->value(hw_get_option('hw_enable_2_child', 0))
    ->styled()
    ->render();

Checkbox::make('hw_enable_teen')
    ->id('enable-teen')
    ->label('Enable Teen Person Type')
    ->setInputLabel('Yes')
    ->value(hw_get_option('hw_enable_teen', 0))
    ->styled()
    ->attributes([
        'x-model' => 'isTeenEnabled'
    ])
    ->render();

Checkbox::make('hw_enable_2_teen')
    ->id('enable-teen-2')
    ->label('Enable Teen 2 Person Type')
    ->setInputLabel('Yes')
    ->value(hw_get_option('hw_enable_2_teen', 0))
    ->styled()
    ->show("isTeenEnabled")
    ->render();

// User Authentication / Account Creation

Heading::make()
    ->heading('User Authentication / Account Creation')
    ->render();

Checkbox::make('hw_booking_form_hide_login_cta')
    ->label('Hide Login CTA')
    ->description('Hide login Call To Action on the form when user is not logged in')
    ->setInputLabel('Yes')
    ->value(hw_hide_form_login_cta())
    ->styled()
    ->render();

Checkbox::make('hw_booking_form_hide_create_account')
    ->label('Hide Create Account Box')
    ->description('Hide the Create Account fields from the form')
    ->setInputLabel('Yes')
    ->value(hw_booking_form_hide_create_account())
    ->styled()
    ->render();

// Dynamic Prices

Heading::make()
    ->heading('Dynamic Prices')
    ->render();

Checkbox::make('hw_enable_dynamic_prices')
    ->label('Enable Dynamic Prices')
    ->setInputLabel('Yes')
    ->value(hw_get_option('hw_enable_dynamic_prices', 0))
    ->styled()
    ->render();

// Availability

Heading::make()
    ->heading('Availability')
    ->render();

Text::make('hw_date_unavailable')
    ->label('Unavailable Date')
    ->value(hw_get_option('hw_date_unavailable', ''))
    ->attributes([
        'autocomplete' => 'off',
    ])
    ->arguments([
         'class' => ['calendar', 'form-control']
    ])
    ->render();

// Inventory

Heading::make()
    ->heading('Inventory')
    ->subHeading('Configure the inventory.')
    ->render();

Checkbox::make('hw_enable_inventory')
    ->label('Enable Inventory')
    ->setInputLabel('Yes')
    ->value(hw_is_inventory_enabled())
    ->styled()
    ->attributes([
        'x-model' => 'enableInventory'
    ])
    ->render();

Select::make('hw_default_quote_availability')
    ->label('Default Availability')
    ->value(hw_get_default_availability())
    ->options([
        '1' => 'Available',
        '0' => 'Not Available'
    ])
    ->description('This option apply as default value for room bloks availability, it is overriden by inventory when enabled')
    ->render();

// quotes

Heading::make()
    ->heading('Quote')
    ->subHeading('Quote configuration.')
    ->render();

Checkbox::make('hw_force_quote_status')
    ->label('Force Quote Status')
    ->description('This option force quote\'s status despite inventory availability')
    ->setInputLabel('Yes')
    ->styled()
    ->value(hw_force_quote_status())
    ->attributes([
        'x-model' => 'forceQuoteStatus'
    ])
    ->render();

Select::make('hw_forced_quote_status')
    ->label('Forced Status')
    ->value(hw_forced_quote_status())
    ->options([
        'pending' => 'Pending',
        'confirmed' => 'Confirmed'
    ])
    ->description('This is the value applied to quotes when "Force Status" is active')
    ->arguments([
        'wrapper_attributes' => [
            'x-show' => "forceQuoteStatus"
        ]
    ])
    ->render();

// Additional notes

Heading::make()
    ->heading('Additional Notes')
    ->subHeading('Configure additional notes box.')
    ->render();

Checkbox::make('hw_enable_additional_notes')
    ->label('Enable Additional Notes')
    ->setInputLabel('Yes')
    ->value(hw_is_additional_notes_enabled())
    ->styled()
    ->attributes([
        'x-model' => 'enableAdditionalNotes'
    ])
    ->render();

Checkbox::make('hw_additional_notes_required')
    ->label('Is Additional Notes Required?')
    ->setInputLabel('Yes')
    ->value(hw_is_additional_notes_is_required())
    ->styled()
    ->arguments([
        'wrapper_attributes' => [
            'x-show' => "enableAdditionalNotes"
        ]
    ])
    ->render();

Text::make('hw_additional_notes_label')
    ->label('Additional Notes Label')
    ->value(hw_get_additional_notes_label())
    ->show('enableAdditionalNotes')
    ->render();

TextArea::make('hw_additional_notes_description')
    ->label('Additional Notes Description')
    ->value(hw_get_additional_notes_description())
    ->show('enableAdditionalNotes')
    ->render();

// transfer

Heading::make()
    ->heading('Transfer')
    ->subHeading('Configure transfer.')
    ->render();

Checkbox::make('hw_has_transfer')
    ->label('Is transfer offered?')
    ->setInputLabel('Yes')
    ->value(hw_is_transfer_enabled())
    ->styled()
    ->attributes([
        'x-model' => 'isTransferEnabled'
    ])
    ->render();

Grid::make()
    ->columns(3)
    ->schema([
        Select::make('hw_transfer_multiplier')
            ->label('Transfer Multiplier')
            ->value(hw_get_transportation_mode())
            ->options([
                'room' => 'Per Room',
                'person' => 'Per Person',
            ])
            ->attributes([
                'x-model' => 'transferMultiplier'
            ]),
        Text::make('hw_service_fee')
            ->label('Transfer Fee')
            ->input('number')
            ->value(hw_get_service_fee()),
        Text::make('hw_service_fee_child')
            ->label('Child Fee')
            ->input('number')
            ->value(hw_get_child_service_fee())
            ->show("transferMultiplier == 'person'")
    ])
    ->show("isTransferEnabled")
    ->render();

// Basic price

Heading::make()
    ->heading('Basic Price')
    ->render();

Checkbox::make('hw_enable_basic_price')
    ->label('Enable Basic Price')
    ->setInputLabel('Yes')
    ->value(hw_get_option('hw_enable_basic_price', 0))
    ->styled()
    ->attributes([
        'x-model' => 'enableBasicPrice'
    ])
    ->render();

Heading::make()
    ->heading('Search Box')
    ->render();

Grid::make()
    ->columns(3)
    ->schema([
        Number::make('hw_cart_max_adults')
            ->label('Max Adults in select box')
            ->input('number')
            ->min(1)
            ->value(hw_get_cart_max_adults()),
        Number::make('hw_cart_max_teen')
            ->label('Max Teen in select box')
            ->input('number')
            ->min(0)
            ->value(hw_get_cart_max_teen()),
        Number::make('hw_cart_max_children')
            ->label('Max Children in select box')
            ->input('number')
            ->min(0)
            ->value(hw_get_cart_max_children()),
    ])
    ->render();