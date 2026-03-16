<?php

namespace App\Http\Controllers;

use App\Models\Prompt;
use App\Models\PromptVersion;
use Illuminate\Routing\Controller;

class InternalApiController extends Controller
{
    public function variables()
    {
        $variables = PromptVersion::whereNotNull('variables')
            ->pluck('variables')
            ->flatten()
            ->unique()
            ->sort()
            ->values();

        return response()->json($variables);
    }

    public function fragments()
    {
        $fragments = Prompt::whereNull('deleted_at')
            ->where(function ($q) {
                $q->whereNotNull('pinned_version_id')
                  ->orWhereHas('versions');
            })
            ->orderBy('name')
            ->get(['slug', 'name']);

        return response()->json($fragments);
    }
}
