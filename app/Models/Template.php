<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Template extends Model
{
    protected $fillable = ['user_id','name','type','body','media_url','variables'];
    protected $casts    = ['variables' => 'array'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    /**
     * Render template body by replacing {{variable}} placeholders.
     */
    public function render(array $data): string
    {
        return preg_replace_callback('/\{\{(\w+)\}\}/', function ($m) use ($data) {
            return $data[$m[1]] ?? $m[0];
        }, $this->body);
    }
}
