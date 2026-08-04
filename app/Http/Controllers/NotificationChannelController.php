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
            // Integrations are the same model but live on their own page.
            ->whereIn('type', ChannelType::basicValues())
            ->withCount('monitors')
            ->orderBy('name')
            ->get();

        return Inertia::render('channels/Index', [
            'channels' => NotificationChannelResource::collection($channels)->resolve(),
            'types' => ChannelType::basicValues(),
        ]);
    }

    public function store(StoreChannelRequest $request)
    {
        $this->abortIfIntegration($request->input('type'));

        $request->user()->notificationChannels()->create($request->channelAttributes());

        return to_route('channels.index')->with('success', __('channels.messages.created.success'));
    }

    public function update(UpdateChannelRequest $request, NotificationChannel $channel)
    {
        $this->abortIfIntegration($request->input('type'));

        $channel->update($request->channelAttributes());

        return to_route('channels.index')->with('success', __('channels.messages.updated.success'));
    }

    /**
     * Integration types belong to IntegrationController; letting them through
     * here would create a channel this page then refuses to list.
     */
    private function abortIfIntegration(mixed $type): void
    {
        abort_if(
            ChannelType::tryFrom((string) $type)?->isIntegration() === true,
            422,
        );
    }

    public function destroy(NotificationChannel $channel)
    {
        $this->authorize('delete', $channel);

        $channel->delete();

        return to_route('channels.index')->with('success', __('channels.messages.deleted.success'));
    }
}
