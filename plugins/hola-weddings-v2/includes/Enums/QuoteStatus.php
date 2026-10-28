<?php

namespace HW\Enums;

enum QuoteStatus: string {
    case CONFIRMED = 'confirmed';
    case PENDING   = 'pending';
    case CANCELED  = 'canceled';
}
