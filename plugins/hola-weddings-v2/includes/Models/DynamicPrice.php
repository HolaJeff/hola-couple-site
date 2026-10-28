<?php

namespace HW\Models;

use DateTime;
use HW\Traits\hasACFFields;

class DynamicPrice extends DecoratorModel{

    use hasACFFields;

    public static $post_type = 'dynamic_prices';
}