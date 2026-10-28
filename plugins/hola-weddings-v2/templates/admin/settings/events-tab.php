<?php

use HW\UI\Forms\Controls\Checkbox;
use HW\UI\Forms\Controls\Repeater;
use HW\UI\Forms\Controls\Text;
use HW\UI\Forms\Layouts\Grid;
use HW\UI\Forms\Layouts\Heading;
use HW\UI\Forms\Layouts\Section;

$events = hw_get_option('hw_events');

Heading::make()
    ->heading('Ceremony')
    ->tag('h4')
    ->render();

Checkbox::make('hw_events[ceremony][active]')
    ->id('has-ceremony')
    ->label('Has Ceremony')
    ->setInputLabel('Yes')
    ->value($events['ceremony']['active'] ?? 0)
    ->attributes([
        'x-model' => 'hasCeremony'
    ])
    ->styled()
    ->render();

Grid::make()
    ->columns(2)
    ->schema([
        Text::make('hw_events[ceremony][time]')
            ->label('Time')
            ->placeholder('Ceremony time')
            ->value($events['ceremony']['time'] ?? ''),
        Text::make('hw_events[ceremony][location]')
            ->label('Location')
            ->placeholder('Ceremony location')
            ->value($events['ceremony']['location'] ?? '')    
    ])
    ->show("hasCeremony")
    ->render();

Heading::make()
    ->heading('Cocktail')
    ->tag('h4')
    ->render();

Checkbox::make('hw_events[cocktail][active]')
    ->id('has-cocktail')
    ->label('Has Cocktail')
    ->setInputLabel('Yes')
    ->value($events['cocktail']['active'] ?? 0)
    ->attributes([
        'x-model' => 'hasCocktail'
    ])
    ->styled()
    ->render();

Grid::make()
    ->columns(2)
    ->schema([
        Text::make('hw_events[cocktail][time]')
            ->label('Time')
            ->placeholder('Cocktail time')
            ->value($events['cocktail']['time'] ?? ''),
        Text::make('hw_events[cocktail][location]')
            ->label('Location')
            ->placeholder('Cocktail location')
            ->value($events['cocktail']['location'] ?? '')    
    ])
    ->show("hasCocktail")
    ->render();

Heading::make()
    ->heading('Reception')
    ->tag('h4')
    ->render();

Checkbox::make('hw_events[reception][active]')
    ->id('has-reception')
    ->label('Has Reception')
    ->setInputLabel('Yes')
    ->value($events['reception']['active'] ?? 0)
    ->attributes([
        'x-model' => 'hasReception'
    ])
    ->styled()
    ->render();

Grid::make()
    ->columns(2)
    ->schema([
        Text::make('hw_events[reception][time]')
            ->label('Time')
            ->placeholder('Reception time')
            ->value($events['reception']['time'] ?? ''),
        Text::make('hw_events[reception][location]')
            ->label('Location')
            ->placeholder('Reception location')
            ->value($events['reception']['location'] ?? '')    
    ])
    ->show("hasReception")
    ->render();

Section::make()
    ->title('Additional Events')
    ->schema([
        Repeater::make('hw_additional_events')
            ->value( hw_get_additional_events() )
            ->columns(3)
            ->schema([
                Text::make('name')->label('Name')->span(3),
                Text::make('date')->label('Date'),
                Text::make('time')->label('Time'),
                Text::make('location')->label('Location'),
                Text::make('dress_code')->label('Dress Code')->span(3),
            ])
    ])
    ->render();