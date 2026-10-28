<?php

namespace HW\Enums;

enum InvoiceStatus: string{
    case PAID = 'paid';
    case NOT_PAID = 'not-paid';
    case PARTIALLY_PAID = 'partially-paid';
    case CANCELED = 'canceled';
}