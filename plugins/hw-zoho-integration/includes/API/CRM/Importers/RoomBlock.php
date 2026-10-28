<?php

namespace HWZoho\API\CRM\Importers;

use com\zoho\crm\api\blueprints\ValidationError;
use com\zoho\crm\api\record\Record;
use HW\Models\DecoratorModel;
use HW\Models\RoomBlock as RoomBlockModel;
use HWZoho\API\CRM\Modules\RoomBlocks;
use HWZoho\API\CRM\DataMappers\DataMapperInterface;
use HWZoho\API\CRM\DataMappers\RoomBlock as RoomBlockDataMapper;

class RoomBlock extends Importer{

    protected function getRecord(string $recordId){
        
        return RoomBlocks::getRecord($recordId);
    }

    protected function createModel(Record $record){

        return RoomBlockModel::create($record->getName());
    }

    protected function getDataMapper(){

        return new RoomBlockDataMapper;
    }

    protected function validate(int $recordId){

        $existing_model = hwzi_get_room_block_by_zoho_id($recordId);

        if($existing_model instanceof RoomBlockModel){

            return new \WP_Error('MODEL_EXISTS', sprintf('Room Block already exists on this website. <a href="%s" target="_blank">Edit</a>', $existing_model->getEditLink()));
        }
        
        return true;
    }
}