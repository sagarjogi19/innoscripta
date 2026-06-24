<?php

namespace App\Http\Resources;

use App\Models\UserPreference;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin UserPreference
 */
class UserPreferenceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'sources' => $this->sources ?? [],
            'categories' => $this->categories ?? [],
            'authors' => $this->authors ?? [],
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
