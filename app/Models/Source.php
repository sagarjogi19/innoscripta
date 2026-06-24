<?php

namespace App\Models;

use App\Models\Concerns\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Source extends Model
{
    use Filterable, HasFactory;

    protected $fillable = ['name', 'slug', 'provider'];

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }
}
