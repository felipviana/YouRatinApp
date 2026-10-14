<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TmdbService;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function movies(Request $request, TmdbService $tmdbService)
    {
        $validated =$request->validate([
            'query' => ['required', 'string', 'min:2', 'max:255'],
        ]);

        $result = $tmdbService->searchMoviesAndTv($validated['query']);

        if (!$result['success']) {
            return response()->json([
                'message' => $result['message'],
                'data' => [],
            ], $result['status'] ?? 500);
        }

        return response()->json([
            'message' => $result['message'],
            'data' => $result['data'],
        ]);
    }
}
