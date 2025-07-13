<?php

namespace App\Model\Product\Writer\Enum;

enum ProductWriteSource: string
{
    case MySQL = 'mysql';
    case CSV = 'csv';
}
