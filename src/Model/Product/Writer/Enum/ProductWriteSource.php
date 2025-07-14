<?php

namespace App\Model\Product\Writer\Enum;

enum ProductWriteSource: string
{
    case MySQL = 'mysql';
    case CSV = 'csv';
    case Elasticsearch = 'elasticsearch';

    public static function allowedCases(): array
    {
        return [
            self::MySQL,
            self::CSV,
        ];
    }
}
