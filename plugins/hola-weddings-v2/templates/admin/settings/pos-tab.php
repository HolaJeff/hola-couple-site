<?php

use HW\UI\Forms\Controls\Select;

Select::make('hw_pos_debug_level')
    ->label('Debug Level')
    ->value(hw_get_pos_debug_level())
    ->options([
        'debug' => 'Debug',
        'info' => 'Info',
        'warning' => 'Warning',
        'error' => 'Error'
    ])
    ->render();
?>