<?php

use HW\UI\Forms\Controls\DatePicker;
use HW\UI\Forms\Controls\Editor;
use HW\UI\Forms\Controls\Text;
use HW\UI\Forms\Layouts\Grid;

Grid::make()
	->columns(2)
	->schema([
		Text::make('hw_currency')
			->label('Currency Code')
			->value(hw_get_option('hw_currency')),
		Text::make('hw_currency_symbol')
			->label('Currency Symbol')
			->value(hw_get_option('hw_currency_symbol'))
	])
	->render();

Grid::make()
	->columns(3)
	->schema([
		DatePicker::make('hw_date')
			->label('Wedding Date')
			->value(hw_get_option('hw_date')),
		DatePicker::make('hw_start_date')
			->label('Start Date')
			->value(hw_get_option('hw_start_date')),
		DatePicker::make('hw_end_date')
			->label('End Date')
			->value(hw_get_option('hw_end_date'))
	])
	->render();

Editor::make('hw_story')
    ->value(hw_get_option('hw_story', ''))
    ->render();
