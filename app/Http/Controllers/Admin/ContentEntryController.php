<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreContentEntryRequest;
use App\Http\Requests\Admin\UpdateContentEntryRequest;
use App\Http\Resources\ContentEntryResource;
use App\Models\ContentEntry;
use App\Policies\ContentEntryPolicy;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Http\Request;
use Inertia\Inertia;

#[UsePolicy(ContentEntryPolicy::class)]
class ContentEntryController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', ContentEntry::class);

        $type = ContentType::tryFrom((string) $request->input('type'));

        $entries = ContentEntry::query()
            ->when($type, fn ($query) => $query->ofType($type))
            ->search($request->string('search')->toString() ?: null)
            ->with('author')
            ->orderByDesc('updated_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('admin/Content', [
            // The editor is a dialog on this page, so each row carries its raw
            // markdown; there is no separate edit request to fetch it.
            'entries' => $entries->through(fn (ContentEntry $entry) => [
                ...(new ContentEntryResource($entry))->resolve(),
                'body' => $entry->body,
            ]),
            'types' => ContentType::values(),
            'filters' => [
                'type' => $type?->value,
                'search' => $request->string('search')->toString() ?: null,
            ],
        ]);
    }

    public function store(StoreContentEntryRequest $request)
    {
        ContentEntry::create([
            ...$request->entryAttributes(),
            'author_id' => $request->user()->id,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('content.messages.created')]);

        return to_route('admin.content.index');
    }

    public function update(UpdateContentEntryRequest $request, ContentEntry $entry)
    {
        $entry->update($request->entryAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('content.messages.updated')]);

        return to_route('admin.content.index');
    }

    public function destroy(ContentEntry $entry)
    {
        $this->authorize('delete', ContentEntry::class);

        $entry->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('content.messages.deleted')]);

        return back();
    }
}
