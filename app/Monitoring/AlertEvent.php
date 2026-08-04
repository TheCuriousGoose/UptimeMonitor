<?php

namespace App\Monitoring;

enum AlertEvent: string
{
    case Down = 'down';
    case Recovered = 'recovered';
}
