<?php
// AI-GENERATED: File-based news/activity repository (GitHub Copilot / ChatGPT), 2026-01-19

namespace App\Repositories;

/**
 * File-based news/event log repository.
 *
 * Stores lightweight "news" events in a JSON Lines file (one JSON object per line).
 * Defaults to `storage/news.jsonl`.
 *
 * This is used as a simple audit/event feed and can be used as a fallback
 * source for the home page activity feed.
 *
 * Entry format:
 * - ts: ISO 8601 timestamp
 * - type: event key (e.g. `transaction.created`)
 * - message: human-readable message
 * - meta: arbitrary metadata array (will be JSON-encoded)
 */
class NewsRepository
{
    /** @var string Absolute path to the JSONL file. */
    private string $file;

    /**
     * @param string|null $file Optional file override for tests.
     */
    public function __construct(?string $file = null)
    {
        $this->file = $file ?? dirname(__DIR__, 2) . '/storage/news.jsonl';
    }

    /**
     * Append an entry to the log.
     *
     * Errors are intentionally ignored (best-effort logging).
     */
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
     * Read the latest entries from the JSONL file.
     *
     * @return array<int, array<string, mixed>> Most recent entries first.
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
