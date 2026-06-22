<?php

namespace App\Domain\Facilities\Models;

use App\Support\Tenancy\BelongsToTenant;
use Database\Factories\ResourceTypeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Тип ресурса клуба.
 *
 * Slug-и стандартные: table | hall | coach | room | venue_whole.
 * Клуб может добавить свои типы.
 */
class ResourceType extends Model
{
    /** @use HasFactory<ResourceTypeFactory> */
    use BelongsToTenant, HasFactory;

    protected static function newFactory(): ResourceTypeFactory
    {
        return ResourceTypeFactory::new();
    }

    protected $fillable = ['club_id', 'slug', 'name'];

    protected static function booted(): void
    {
        parent::booted();
    }

    public function resources(): HasMany
    {
        return $this->hasMany(Resource::class);
    }
}
