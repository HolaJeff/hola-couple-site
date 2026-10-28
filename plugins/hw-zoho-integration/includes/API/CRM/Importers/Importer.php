<?php

namespace HWZoho\API\CRM\Importers;

use WP_Error;
use com\zoho\crm\api\record\APIException;
use com\zoho\crm\api\record\Record;
use HW\Models\DecoratorModel;
use HWZoho\API\CRM\DataMappers\DataMapperInterface;

abstract class Importer implements ImporterInterface{

    abstract protected function getRecord(string $recordId);

    abstract protected function getDataMapper();

    abstract protected function createModel(Record $record);

    /**
     * process the import
     *
     * @param string $recordId
     * @return DecoratorModel|\WP_Error
     */
    public function import(string $recordId){

        if(is_wp_error($validation = $this->validate($recordId))){

            return $validation;
        }

        $record = $this->getRecord($recordId);

        if($record instanceof APIException){

            return new \WP_Error($record->getCode(), $record->getMessage());
        }

        $dataMapper = $this->getDataMapper();
        $model = $this->createModel($record);

        $dataMapper->map($record, $model);
        
        return $model;
    }

    /**
     * allow child classes to apply validation for the import
     *
     * @return \WP_Error|bool
     */
    protected function validate(int $recordId){

        return true;
    }
}