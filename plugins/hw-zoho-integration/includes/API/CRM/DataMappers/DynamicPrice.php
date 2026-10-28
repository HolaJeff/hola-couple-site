<?php

namespace HWZoho\API\CRM\DataMappers;

use com\zoho\crm\api\record\Record;
use HW\Models\DecoratorModel;

class DynamicPrice extends DataMapper{

    public function Date($value, $model, $record){

        if(is_null($value)){
            return;
        }

        $date = new \DateTime($value);
        
        $model->set('from', $date->format('Ymd'));
        $model->set('to', $date->modify('+1 day')->format('Ymd'));

        $child_price = $record->getKeyValue('Child');
        $teen_price = $record->getKeyValue('Teen');

        $prices = [
            'single' => [
                'adult' => $record->getKeyValue('Single'),
                'child' => $child_price,
                'teen' => $teen_price
            ],
            'double' => [
                'adult' => $record->getKeyValue('Double'),
                'child' => $child_price,
                'teen' => $teen_price
            ],
            'triple' => [
                'adult' => $record->getKeyValue('Triple'),
                'child' => $child_price,
                'teen' => $teen_price
            ],
            'quadruple' => [
                'adult' => $record->getKeyValue('Quad'),
                'child' => $child_price,
                'teen' => $teen_price
            ]
        ];

        $model->set('prices', $prices);
    }
}