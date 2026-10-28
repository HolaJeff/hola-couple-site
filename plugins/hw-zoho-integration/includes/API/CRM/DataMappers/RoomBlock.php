<?php

namespace HWZoho\API\CRM\DataMappers;

use com\zoho\crm\api\record\Record;
use HW\Models\DecoratorModel;
use HW\Models\DynamicPrice;
use HWZoho\API\CRM\DataMappers\DynamicPrice as DataMappersDynamicPrice;

class RoomBlock extends DataMapper{

    public function getAttributesMap()
    {
        return [
            'id' => 'hwzi_room_block_id',
            'October_Room_ID' => 'hw_room_id'
        ];
    }

    /**
     * custom method to handle room and rates field
     *
     * @param mixed $value
     * @param DecoratorModel $model
     * @param Record $record
     * @return void
     */
    public function roomsAndRates($value, $model, $record){

        if(is_null($value)){
            return;
        }

        $inventory = [];

        // subforms return an array of Records
        foreach($value as $subrecord){

            $meta_data = [
                'hw_room_block' => $model->getID()
            ];

            // create a new dynamic price, use the room block name
            $dynamicPriceModel = DynamicPrice::create($record->getName(), $meta_data);

            // map the record
            $dynamicPriceMapper = new DataMappersDynamicPrice;
            $dynamicPriceMapper->map($subrecord, $dynamicPriceModel);

            // set inventory
            $inventory[$subrecord->getKeyValue('Date')] = $subrecord->getKeyValue('Rooms_Reserved') ?? 1;
        }

        $model->set('hw_inventory', $inventory);
    }

    /**
     * allow child classes to do more complicated manipulation
     *
     * @param Record $record
     * @param DecoratorModel $model
     * @return void
     */
    protected function processRecord( Record $record, DecoratorModel &$model ){

        $occupancy_rules = [
            'single' => [
                'adult' => $record->getKeyValue('Single'),
                'child' => $record->getKeyValue('Child'),
                'teen' => $record->getKeyValue('Teen')
            ],
            'double' => [
                'adult' => $record->getKeyValue('Double'),
                'child' => $record->getKeyValue('Child'),
                'teen' => $record->getKeyValue('Teen')
            ],
            'triple' => [
                'adult' => $record->getKeyValue('Triple'),
                'child' => $record->getKeyValue('Child'),
                'teen' => $record->getKeyValue('Teen')
            ],
            'quadruple' => [
                'adult' => $record->getKeyValue('Quad'),
                'child' => $record->getKeyValue('Child'),
                'teen' => $record->getKeyValue('Teen')
            ]
        ];

        $model->set('hw_occupancy_rules', $occupancy_rules);
    }
}
