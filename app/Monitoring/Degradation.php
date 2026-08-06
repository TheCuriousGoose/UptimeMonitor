<?php

namespace App\Monitoring;

/**
 * The latency edge a check crossed, if any.
 *
 * Separate from Transition because the two are independent: a monitor can go
 * down while degraded, and the degradation is then cleared silently rather
 * than announced as an improvement.
 */
enum Degradation
{
    case Began;
    case Ended;
    case None;
}
