<?php

namespace HW\Enums;

enum TransactionStatus: string{
    case CONFIRMED = 'confirmed';
    case PENDING = 'pending';
    case FAILED = 'failed';
    case PROCESSING = 'processing';
    case CANCELED = 'canceled';
}