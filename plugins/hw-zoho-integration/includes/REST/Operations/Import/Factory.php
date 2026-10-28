<?php

namespace HWZoho\REST\Operations\Import;

use HWZoho\REST\Operations\Import\Importers\Invoice;
use HWZoho\REST\Operations\Import\Importers\Quote;
use InvalidArgumentException;

class Factory{

    /**
     * return the instance of an Adapter
     *
     * @param string $type
     * @return Importer
     */
    public static function create(string $type)
    {
        switch ($type) {
            case 'invoices':
                return new Invoice();
                break;
            case 'quote':
                return new Quote();
                break;
            default:
                throw new InvalidArgumentException('Unknown importer type given');
                break;
        };
    }
}