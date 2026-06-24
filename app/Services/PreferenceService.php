<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Support\Str;

class PreferenceService
{
    public function for(User $user): UserPreference
    {
        $user->preference()->firstOrCreate([]);

        // Return a freshly retrieved instance so resource responses are a
        // consistent 200 (firstOrCreate would otherwise flag it as created/201).
        return $user->preference()->firstOrFail();
    }

    /**
     * Store the user's preferences. Values are normalised to slugs so they
     * line up with how sources / categories / authors are stored.
     *
     * @param  array{sources?: array, categories?: array, authors?: array}  $data
     */
    public function update(User $user, array $data): UserPreference
    {
        $preference = $this->for($user);

        $preference->fill(array_filter([
            'sources' => $this->normalise($data['sources'] ?? null),
            'categories' => $this->normalise($data['categories'] ?? null),
            'authors' => $this->normalise($data['authors'] ?? null),
        ], static fn ($value) => $value !== null));

        $preference->save();

        return $preference;
    }

    private function normalise(?array $values): ?array
    {
        if ($values === null) {
            return null;
        }

        return array_values(array_unique(array_map(
            static fn (string $value) => Str::slug($value),
            $values,
        )));
    }
}
