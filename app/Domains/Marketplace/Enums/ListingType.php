<?php

namespace App\Domains\Markeplace\Enums;

use BenSampo\Enum\Enum;

final class ListingType extends Enum
{
    public const SELL = 'sell';
    public const TRADE = 'trande';
    public const AUCTION = 'auction';
}
