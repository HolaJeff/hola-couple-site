<?php

use HW\UI\Forms\Controls\Select;
use HW\UI\Forms\Controls\Text;

$options = hwzi_get_tour_operator_field_options();

Text::make('hwz_wedding_id')
    ->label('ZOHO Wedding ID')
    ->value(hw_get_option('hwz_wedding_id', ''))
    ->render();

if(!empty($options)):
    
    Select::make('hwzi_wedding_group_type')
    ->label('Wedding Group Type')
    ->value(hw_get_option('hwzi_wedding_group_type', ''))
    ->options($options)
    ->description('Options are pulled from Zoho.')
    ->render();
    ?>
<?php else: ?>
    <p><?= __('Wedding Group Type: No options available on Zoho field to display. Go to Zoho and add options to the fields.') ?></p>
<?php endif;