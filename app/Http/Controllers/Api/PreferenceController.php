<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePreferenceRequest;
use App\Http\Resources\UserPreferenceResource;
use App\Services\PreferenceService;
use Illuminate\Http\Request;

class PreferenceController extends Controller
{
    public function __construct(private readonly PreferenceService $preferences) {}

    public function show(Request $request): UserPreferenceResource
    {
        return new UserPreferenceResource($this->preferences->for($request->user()));
    }

    public function update(UpdatePreferenceRequest $request): UserPreferenceResource
    {
        $preference = $this->preferences->update($request->user(), $request->validated());

        return new UserPreferenceResource($preference);
    }
}
