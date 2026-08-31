<?php

namespace App\Enums;

enum FoodCategory: string
{
    case Produce = 'produce';
    case Dairy = 'dairy';
    case Bakery = 'bakery';
    case Prepared = 'prepared';
    case Other = 'other';
}
