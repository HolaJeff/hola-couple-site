<?php

namespace HWZoho\API\CRM\Adapters;

class Factory{

    public static function get($model){

        switch($model->getPostType()){
            case 'invoices':
                return new Invoices($model);
                break;
        }
    }
}