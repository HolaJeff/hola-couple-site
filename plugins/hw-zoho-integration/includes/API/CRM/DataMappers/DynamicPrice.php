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
        
        // one rule per NIGHT: from = to = that night. (Was to = +1 day, so two rules matched
        // every night and the earlier night's rate won: a rate break started one night late.)
        $model->set('from', $date->format('Ymd'));
        $model->set('to', $date->format('Ymd'));

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