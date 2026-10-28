<?php

namespace HWZoho\API\CRM\Importers;

use com\zoho\crm\api\record\Record;
use HW\Models\DecoratorModel;
use WP_Error;

interface ImporterInterface{

    public function import(string $recordId);
}