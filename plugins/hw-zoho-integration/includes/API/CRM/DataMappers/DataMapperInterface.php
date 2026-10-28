<?php

namespace HWZoho\API\CRM\DataMappers;

use com\zoho\crm\api\record\Record;
use HW\Models\DecoratorModel;

interface DataMapperInterface{

    public function map(Record $record, DecoratorModel $model);
}