<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class TmdbService
{
    private string $baseUrl;
    private string $imageBaseUrl;
    private ?string $accessToken;

    public function __construct()
    {
        $this->baseUrl = config('services.tmdb.base_url');
        $this->imageBaseUrl = config('services.tmdb.image_base_url');
        $this->accessToken = config('services.tmdb.access_token');
    }

    public function searchMoviesAndTv(string $query): array
    {
        $response = Http::withToken($this->accessToken)
            ->acceptJson()
            ->get("{$this->baseUrl}/search/multi", [
                'query' => $query,
                'language' => 'pt-BR',
                'include_adult' => false,
            ]);

        if ($response->failed()) {
            return [
                'success' => false,
                'message' => 'Erro ao consultar a TMDb.',
                'status' => $response->status(),
                'data' => [],
            ];
        }

        $results = collect($response->json('results', []))
            ->filter(function ($item) {
                return in_array($item['media_type'] ?? null, ['movie', 'tv']);
            })
            ->map(function ($item) {
                return $this->formatMediaItem($item);
            })
            ->values()
            ->toArray();

        return [
            'success' => true,
            'message' => 'Busca realizada com sucesso.',
            'data' => $results,
        ];
    }

    private function formatMediaItem(array $item): array
    {
        $mediaType = $item['media_type'] ?? null;

        $title = $mediaType === 'movie'
            ? ($item['title'] ?? 'Sem título')
            : ($item['name'] ?? 'Sem título');

        $releaseDate = $mediaType === 'movie'
            ? ($item['release_date'] ?? null)
            : ($item['first_air_date'] ?? null);

        return [
            'external_id' => $item['id'] ?? null,
            'external_source' => 'tmdb',
            'media_type' => $mediaType,
            'title' => $title,
            'overview' => $item['overview'] ?? null,
            'poster_path' => $item['poster_path'] ?? null,
            'poster_url' => $this->buildPosterUrl($item['poster_path'] ?? null),
            'release_date' => $releaseDate,
            'vote_average' => $item['vote_average'] ?? 0,
            'original_language' => $item['original_language'] ?? null,
        ];
    }

    private function buildPosterUrl(?string $posterPath): ?string
    {
        if (!$posterPath) {
            return null;
        }

        return "{$this->imageBaseUrl}{$posterPath}";
    }
}