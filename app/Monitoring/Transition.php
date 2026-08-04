<?php

namespace App\Monitoring;

enum Transition
{
    case WentDown;
    case Recovered;
    case None;
}
