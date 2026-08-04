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

/**
 * Integrations are NotificationChannels whose type is an alerting product
 * rather than a plain message sink. They share the model, policy, and
 * validation with notification channels — only the surface differs.
 */
#[UsePolicy(NotificationChannelPolicy::class)]
class IntegrationController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', NotificationChannel::class);

        $connected = NotificationChannel::query()
            ->where('user_id', Auth::id())
            ->whereIn('type', ChannelType::integrationValues())
            ->withCount('monitors')
            ->orderBy('name')
            ->get();

        return Inertia::render('integrations/Index', [
            'integrations' => NotificationChannelResource::collection($connected)->resolve(),
            'providers' => ChannelType::integrationValues(),
        ]);
    }

    public function store(StoreChannelRequest $request)
    {
        $this->abortUnlessIntegration($request->input('type'));

        $request->user()->notificationChannels()->create($request->channelAttributes());

        return to_route('integrations.index')
            ->with('success', __('integrations.messages.connected.success'));
    }

    public function update(UpdateChannelRequest $request, NotificationChannel $integration)
    {
        $this->abortUnlessIntegration($request->input('type'));

        $integration->update($request->channelAttributes());

        return to_route('integrations.index')
            ->with('success', __('integrations.messages.updated.success'));
    }

    public function destroy(NotificationChannel $integration)
    {
        $this->authorize('delete', $integration);

        $integration->delete();

        return to_route('integrations.index')
            ->with('success', __('integrations.messages.disconnected.success'));
    }

    /**
     * Keeps the two surfaces honest: this controller must not be used to
     * create an email channel, nor the channels controller a PagerDuty one.
     */
    private function abortUnlessIntegration(mixed $type): void
    {
        abort_unless(
            ChannelType::tryFrom((string) $type)?->isIntegration() === true,
            422,
        );
    }
}
