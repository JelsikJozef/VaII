<?php
// AI-GENERATED: File-based news/activity repository (GitHub Copilot / ChatGPT), 2026-01-19

namespace App\Repositories;

class NewsRepository
{
    private string $file;

    public function __construct(?string $file = null)
    {
        $this->file = $file ?? dirname(__DIR__, 2) . '/storage/news.jsonl';
    }

    public function log(string $type, string $message, array $meta = []): void
    {
        $dir = dirname($this->file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $entry = [
            'ts' => date('c'),
            'type' => $type,
            'message' => $message,
            'meta' => $meta,
        ];

        $line = json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($line === false) {
            return;
        }

        @file_put_contents($this->file, $line . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function latest(int $limit = 10): array
    {
        if (!is_file($this->file)) {
            return [];
        }

        $lines = @file($this->file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!is_array($lines) || $lines === []) {
            return [];
        }

        $lines = array_slice($lines, -1 * max(1, $limit));
        $items = [];
        foreach (array_reverse($lines) as $line) {
            $decoded = json_decode($line, true);
            if (is_array($decoded)) {
                $items[] = $decoded;
            }
        }

        return $items;
    }
}
