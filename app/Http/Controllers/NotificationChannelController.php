<?php

namespace App\Http\Controllers;

use App\Enums\ChannelType;
use App\Http\Requests\Channels\StoreChannelRequest;
use App\Http\Requests\Channels\UpdateChannelRequest;
use App\Http\Resources\NotificationChannelResource;
use App\Models\NotificationChannel;
use App\Policies\NotificationChannelPolicy;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

#[UsePolicy(NotificationChannelPolicy::class)]
class NotificationChannelController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', NotificationChannel::class);

        $channels = NotificationChannel::query()
            ->where('user_id', Auth::id())
            ->withCount('monitors')
            ->orderBy('name')
            ->get();

        return Inertia::render('channels/Index', [
            'channels' => NotificationChannelResource::collection($channels)->resolve(),
            'types' => ChannelType::values(),
        ]);
    }

    public function store(StoreChannelRequest $request)
    {
        $request->user()->notificationChannels()->create($request->channelAttributes());

        return to_route('channels.index')->with('success', __('channels.messages.created.success'));
    }

    public function update(UpdateChannelRequest $request, NotificationChannel $channel)
    {
        $channel->update($request->channelAttributes());

        return to_route('channels.index')->with('success', __('channels.messages.updated.success'));
    }

    public function destroy(NotificationChannel $channel)
    {
        $this->authorize('delete', $channel);

        $channel->delete();

        return to_route('channels.index')->with('success', __('channels.messages.deleted.success'));
    }
}
