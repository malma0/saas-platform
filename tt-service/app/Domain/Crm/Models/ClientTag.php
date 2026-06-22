<?php

namespace App\Domain\Crm\Models;

use App\Support\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ClientTag extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'club_id',
        'name',
        'color',
    ];

    public function clients(): BelongsToMany
    {
        return $this->belongsToMany(Client::class, 'client_tag_pivot');
    }
}
