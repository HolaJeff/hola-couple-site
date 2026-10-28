<?php

use HW\UI\Forms\Controls\Checkbox;
use HW\UI\Forms\Controls\DatePicker;
use HW\UI\Forms\Controls\Number;
use HW\UI\Forms\Controls\Select;
use HW\UI\Forms\Controls\Text;
use HW\UI\Forms\Layouts\Section;

$deposit_types = [
	'fixed' => __('Fixed', 'hola-weddings'),
	'dynamic' => __('Dynamic', 'hola-weddings')
];

Checkbox::make('hw_enable_payment')
    ->id('enable-payment')
    ->label(__('Enable Payment Form', 'hola-weddings'))
    ->setInputLabel('Yes')
    ->value(is_payment_enabled())
    ->styled()
	->attributes([
        'x-model' => 'enablePaymentForm'
    ])
    ->render();

Section::make()
	->title('Deposit')
	->columns(3)
	->schema([
		Select::make('hw_deposit_type')
			->label(__('Deposit Type', 'hola-weddings'))
			->value(hw_get_option('hw_deposit_type', 'fixed'))
			->options([
				'fixed' => __('Fixed', 'hola-weddings'),
				'dynamic' => __('Dynamic', 'hola-weddings')
			])
			->attributes([
				'x-model' => 'depositType'
			])
			->span(3),
		Number::make('hw_min_deposit')
			->label(__('Minimum Deposit Amount', 'hola-weddings'))
			->value(hw_get_option('hw_min_deposit', 200))
			,
		Number::make('hw_deposit_nights_multiplier')
			->label(__('Nights Multiplier', 'hola-weddings'))
			->value(hw_get_option('hw_deposit_nights_multiplier', 1))
			->show("depositType == 'dynamic'"),
		DatePicker::make('hw_deposit_increase_date')
			->label(__('Increase Date', 'hola-weddings'))
			->value(hw_get_option('hw_deposit_increase_date', ''))
			->show("depositType != 'dynamic'"),
		Number::make('hw_deposit_increase_amount')
			->label(__('Increase Amount', 'hola-weddings'))
			->value(hw_get_option('hw_deposit_increase_amount', ''))
			->show("depositType != 'dynamic'"),
	])
	->show("enablePaymentForm")
	->render();
