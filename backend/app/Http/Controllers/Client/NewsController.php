<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class NewsController extends Controller
{
    private const RSS_FEEDS = [
        'monde'  => 'https://feeds.bbci.co.uk/news/world/rss.xml',
        'tech'   => 'https://feeds.bbci.co.uk/news/technology/rss.xml',
        'sport'  => 'https://feeds.bbci.co.uk/news/sport/rss.xml',
    ];

    public function index(string $category = 'monde'): JsonResponse
    {
        $url = self::RSS_FEEDS[$category] ?? self::RSS_FEEDS['monde'];

        $articles = Cache::remember("news_{$category}", 300, function () use ($url) {
            return $this->fetchRss($url);
        });

        return response()->json([
            'category' => $category,
            'articles' => $articles,
        ]);
    }

    private function fetchRss(string $url): array
    {
        try {
            $response = Http::timeout(5)->get($url);
            if (!$response->successful()) {
                return $this->fallbackArticles();
            }

            $xml = simplexml_load_string($response->body(), 'SimpleXMLElement', LIBXML_NOCDATA);
            if (!$xml) {
                return $this->fallbackArticles();
            }

            $articles = [];
            foreach ($xml->channel->item as $item) {
                $articles[] = [
                    'title'       => (string) $item->title,
                    'description' => strip_tags((string) $item->description),
                    'link'        => (string) $item->link,
                    'published_at'=> (string) $item->pubDate,
                    'image'       => $this->extractImage($item),
                ];
                if (count($articles) >= 15) break;
            }

            return $articles ?: $this->fallbackArticles();
        } catch (\Throwable) {
            return $this->fallbackArticles();
        }
    }

    private function extractImage(\SimpleXMLElement $item): ?string
    {
        // Try media:thumbnail
        $media = $item->children('http://search.yahoo.com/mrss/');
        if (isset($media->thumbnail)) {
            $attrs = $media->thumbnail->attributes();
            return (string) ($attrs['url'] ?? '');
        }
        // Try enclosure
        if (isset($item->enclosure)) {
            $attrs = $item->enclosure->attributes();
            if (str_contains((string) ($attrs['type'] ?? ''), 'image')) {
                return (string) ($attrs['url'] ?? '');
            }
        }
        return null;
    }

    private function fallbackArticles(): array
    {
        return [
            [
                'title'        => 'Bienvenue dans la salle d\'attente digitale',
                'description'  => 'Profitez de votre temps d\'attente avec nos jeux et actualités. Vous serez notifié dès que votre ticket est appelé.',
                'link'         => '',
                'published_at' => now()->toRfc2822String(),
                'image'        => null,
            ],
            [
                'title'        => 'Temps d\'attente estimé mis à jour',
                'description'  => 'Votre position dans la file est mise à jour en temps réel. Restez à proximité pour ne pas manquer votre appel.',
                'link'         => '',
                'published_at' => now()->subMinutes(5)->toRfc2822String(),
                'image'        => null,
            ],
        ];
    }
}
