<?php

use HW\UI\Forms\Controls\ImageUpload;
use HW\UI\Forms\Controls\Text;
use HW\UI\Forms\Controls\TextArea;
use HW\UI\Forms\Layouts\Grid;
use HW\UI\Forms\Layouts\Section;

Grid::make()
    ->columns(2)
    ->schema([
        Section::make()
            ->title('Bride')
            ->schema([
                Text::make('hw_her_name')
                    ->label('Name')
                    ->value(hw_get_option('hw_her_name'))
                    ->placeholder('Bride Name'),
                ImageUpload::make('hw_bride_image')
                    ->id('bride_image')
                    ->label('Picture')
                    ->value(hw_get_option('hw_bride_image', '' )),
                TextArea::make('hw_bride_bio')
                    ->label('Bio')
                    ->value(hw_get_option('hw_bride_bio'))
                    ->placeholder('Write here the bio')
            ]),
        Section::make()
            ->title('Groom')
            ->schema([
                Text::make('hw_his_name')
                    ->label('Name')
                    ->value(hw_get_option('hw_his_name'))
                    ->placeholder('Groom Name'),
                ImageUpload::make('hw_groom_image')
                    ->id('groom_image')
                    ->label('Picture')
                    ->value(hw_get_option('hw_groom_image', '' )),
                TextArea::make('hw_groom_bio')
                    ->label('Bio')
                    ->value(hw_get_option('hw_groom_bio'))
                    ->placeholder('Write here the bio')
            ])
    ])
    ->render();