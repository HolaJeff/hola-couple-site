<?php

use HW\UI\Forms\Controls\Gallery;
use HW\UI\Forms\Controls\ImageUpload;
use HW\UI\Forms\Layouts\Grid;
use HW\UI\Forms\Layouts\Section;

Grid::make()
    ->columns(2)
    ->schema([
        Section::make()
            ->title('Main Image')
            ->schema([
                ImageUpload::make('hw_main_image')
                    ->id('main_image')
                    ->value(hw_get_option('hw_main_image', '' ))
            ]),
        Section::make()
            ->title('Story Image')
            ->schema([
                ImageUpload::make('hw_story_image')
                    ->id('story_image')
                    ->value(hw_get_option('hw_story_image', '' ))
            ])
    ])
    ->render();

Section::make()
    ->title('Product Gallery')
    ->schema([
        Gallery::make('hw_product_image_gallery')
            ->addLabel('Select Images')
            ->value(hw_get_option('hw_product_image_gallery', '' ))

    ])
    ->render();