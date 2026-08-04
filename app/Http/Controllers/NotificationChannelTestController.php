<?php

namespace App\Http\Controllers;

use App\Jobs\SendAlert;
use App\Models\Monitor;
use App\Models\NotificationChannel;
use App\Monitoring\AlertMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class NotificationChannelTestController extends Controller
{
    /**
     * Send a sample alert so the user can confirm a channel works before
     * relying on it during a real outage.
     */
    public function store(NotificationChannel $channel): RedirectResponse
    {
        $this->authorize('update', $channel);

        $sample = Auth::user()->monitors()->first() ?? new Monitor([
            'name' => __('channels.test.sample_monitor'),
            'url' => config('app.url'),
        ]);

        SendAlert::dispatch($channel, AlertMessage::down($sample, __('channels.test.sample_error')));

        return back()->with('success', __('channels.messages.tested.success'));
    }
}
