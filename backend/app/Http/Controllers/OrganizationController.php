<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrganizationRequest;
use App\Jobs\ParseOrganizationReviews;
use App\Models\Organization;
use App\Services\YandexMaps\OrganizationUrlParser;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
    public function __construct(private readonly OrganizationUrlParser $urlParser)
    {
    }

    public function show(Request $request)
    {
        $organization = $request->user()->organizations()->latest()->first();

        return response()->json([
            'organization' => $organization,
        ]);
    }

    public function store(StoreOrganizationRequest $request)
    {
        $yandexUrl = $request->string('yandex_url')->toString();
        $yandexId = $this->urlParser->extractOrganizationId($yandexUrl);

        $organization = Organization::updateOrCreate(
            ['yandex_id' => $yandexId],
            [
                'user_id' => $request->user()->id,
                'yandex_url' => $yandexUrl,
                'status' => 'pending',
                'last_error' => null,
            ]
        );

        ParseOrganizationReviews::dispatch($organization);

        return response()->json([
            'organization' => $organization,
        ], 201);
    }
}
