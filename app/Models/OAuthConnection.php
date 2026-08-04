<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'provider', 'provider_id'])]
class OAuthConnection extends Model
{
    /**
     * Eloquent would infer "o_auth_connections" from the OAuth prefix, but the
     * migration creates "oauth_connections".
     */
    protected $table = 'oauth_connections';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
