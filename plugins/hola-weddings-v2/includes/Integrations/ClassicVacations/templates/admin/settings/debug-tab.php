<?php

use HW\UI\Forms\Controls\Select;

?>
<?php
Select::make('data[hw_cv_debug_level]')
    ->label('Debug Level')
    ->value(hw_get_cv_debug_level())
    ->options([
        'debug' => 'Debug',
        'info' => 'Info',
        'warning' => 'Warning',
        'error' => 'Error'
    ])
    ->render();

?>