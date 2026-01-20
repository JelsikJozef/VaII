<?php

namespace App\Services;

/**
 * Converts Markdown to HTML and sanitizes it to an allowlist to prevent XSS.
 */
class MarkdownRenderer
{
    /** @var array<string,bool> */
    private array $allowedTags = [
        'p' => true,
        'br' => true,
        'h1' => true,
        'h2' => true,
        'h3' => true,
        'h4' => true,
        'h5' => true,
        'h6' => true,
        'ul' => true,
        'ol' => true,
        'li' => true,
        'strong' => true,
        'em' => true,
        'code' => true,
        'pre' => true,
        'blockquote' => true,
        'a' => true,
    ];

    /** @var array<string,array<string,bool>> */
    private array $allowedAttributes = [
        'a' => [
            'href' => true,
            'title' => true,
            'target' => true,
            'rel' => true,
        ],
    ];

    /**
     * Render Markdown as sanitized HTML safe for raw output in views.
     */
    public function toSafeHtml(string $markdown): string
    {
        $html = $this->parseMarkdown($markdown);
        return $this->sanitize($html);
    }

    private function parseMarkdown(string $markdown): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $markdown);
        $lines = explode("\n", $text);

        $html = '';
        $inCode = false;
        $codeBuffer = [];
        $listType = null; // 'ul' or 'ol'
        $paragraphBuffer = [];

        $flushParagraph = function () use (&$paragraphBuffer, &$html) {
            if (!empty($paragraphBuffer)) {
                $content = implode(' ', $paragraphBuffer);
                $html .= '<p>' . $this->parseInline($content) . '</p>';
                $paragraphBuffer = [];
            }
        };

        $closeList = function () use (&$listType, &$html) {
            if ($listType !== null) {
                $html .= '</' . $listType . '>';
                $listType = null;
            }
        };

        foreach ($lines as $line) {
            // Close code block
            if ($inCode && preg_match('/^```/', $line)) {
                $html .= '<pre><code>' . htmlspecialchars(implode("\n", $codeBuffer), ENT_QUOTES) . '</code></pre>';
                $codeBuffer = [];
                $inCode = false;
                continue;
            }

            if ($inCode) {
                $codeBuffer[] = $line;
                continue;
            }

            // Opening code fence
            if (preg_match('/^```/', $line)) {
                $flushParagraph();
                $closeList();
                $inCode = true;
                $codeBuffer = [];
                continue;
            }

            // Blank line closes paragraphs and lists
            if (trim($line) === '') {
                $flushParagraph();
                $closeList();
                continue;
            }

            // Headings
            if (preg_match('/^(#{1,6})\s+(.*)$/', $line, $m)) {
                $flushParagraph();
                $closeList();
                $level = strlen($m[1]);
                $textContent = trim($m[2]);
                $html .= '<h' . $level . '>' . $this->parseInline($textContent) . '</h' . $level . '>';
                continue;
            }

            // Blockquote
            if (preg_match('/^>\s?(.*)$/', $line, $m)) {
                $flushParagraph();
                $closeList();
                $html .= '<blockquote><p>' . $this->parseInline(trim($m[1])) . '</p></blockquote>';
                continue;
            }

            // Lists
            if (preg_match('/^\s*([-*])\s+(.+)$/', $line, $m)) {
                $flushParagraph();
                if ($listType !== 'ul') {
                    $closeList();
                    $listType = 'ul';
                    $html .= '<ul>';
                }
                $html .= '<li>' . $this->parseInline(trim($m[2])) . '</li>';
                continue;
            }

            if (preg_match('/^\s*\d+\.\s+(.+)$/', $line, $m)) {
                $flushParagraph();
                if ($listType !== 'ol') {
                    $closeList();
                    $listType = 'ol';
                    $html .= '<ol>';
                }
                $html .= '<li>' . $this->parseInline(trim($m[1])) . '</li>';
                continue;
            }

            // Default: accumulate paragraph
            $paragraphBuffer[] = $line;
        }

        // Close any open structures
        if ($inCode) {
            $html .= '<pre><code>' . htmlspecialchars(implode("\n", $codeBuffer), ENT_QUOTES) . '</code></pre>';
        }
        if (!empty($paragraphBuffer)) {
            $html .= '<p>' . $this->parseInline(implode(' ', $paragraphBuffer)) . '</p>';
        }
        if ($listType !== null) {
            $html .= '</' . $listType . '>';
        }

        return $html;
    }

    private function parseInline(string $text): string
    {
        $segments = preg_split('/(`[^`]*`)/', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        $html = '';

        foreach ($segments as $segment) {
            if ($segment === '') {
                continue;
            }

            if ($segment[0] === '`' && substr($segment, -1) === '`' && strlen($segment) >= 2) {
                $codeContent = substr($segment, 1, -1);
                $html .= '<code>' . htmlspecialchars($codeContent, ENT_QUOTES) . '</code>';
                continue;
            }

            $escaped = htmlspecialchars($segment, ENT_QUOTES);

            // Bold
            $escaped = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $escaped);
            // Italic
            $escaped = preg_replace('/\*(.+?)\*/s', '<em>$1</em>', $escaped);
            // Links
            $escaped = preg_replace_callback('/\[([^\]]+)\]\(([^\)]+)\)/', function ($m) {
                $text = htmlspecialchars($m[1], ENT_QUOTES);
                $href = htmlspecialchars($m[2], ENT_QUOTES);
                return '<a href="' . $href . '">' . $text . '</a>';
            }, $escaped);

            $html .= $escaped;
        }

        return $html;
    }

    private function sanitize(string $html): string
    {
        $dom = new \DOMDocument();
        $internalErrors = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8"?><div>' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($internalErrors);

        $wrapper = $dom->getElementsByTagName('div')->item(0);
        if ($wrapper === null) {
            return '';
        }

        // Sanitize only the children to avoid stripping the artificial wrapper.
        foreach (iterator_to_array($wrapper->childNodes) as $child) {
            $this->sanitizeNode($child);
        }

        $safeHtml = '';
        foreach (iterator_to_array($wrapper->childNodes) as $child) {
            $safeHtml .= $dom->saveHTML($child);
        }

        return $safeHtml;
    }

    private function sanitizeNode(\DOMNode $node): void
    {
        if ($node instanceof \DOMElement) {
            $tag = strtolower($node->tagName);

            if (!isset($this->allowedTags[$tag])) {
                $this->unwrapNode($node);
                return;
            }

            // Drop any disallowed or dangerous attributes.
            foreach (iterator_to_array($node->attributes) as $attribute) {
                $name = strtolower($attribute->name);
                if ($this->startsWith($name, 'on')) {
                    $node->removeAttributeNode($attribute);
                    continue;
                }
                if ($tag !== 'a' || !isset($this->allowedAttributes['a'][$name])) {
                    $node->removeAttributeNode($attribute);
                }
            }

            if ($tag === 'a') {
                $href = $node->getAttribute('href');
                if (!$this->isAllowedHref($href)) {
                    // Unwrap invalid links entirely to remove clickable payloads.
                    $this->unwrapNode($node);
                    return;
                }

                // External links get safe target/rel.
                if ($this->isExternalHref($href)) {
                    $node->setAttribute('target', '_blank');
                    $existingRel = trim($node->getAttribute('rel'));
                    $tokens = $existingRel === '' ? [] : preg_split('/\s+/', $existingRel);
                    $tokens = array_filter(array_unique(array_merge($tokens ?: [], ['noopener', 'noreferrer'])));
                    $node->setAttribute('rel', implode(' ', $tokens));
                } else {
                    // Keep rel only if present and non-empty.
                    if (trim($node->getAttribute('rel')) === '') {
                        $node->removeAttribute('rel');
                    }
                    if (!$this->isExternalHref($href)) {
                        $node->removeAttribute('target');
                    }
                }
            } else {
                // Non-link elements must not carry attributes.
                foreach (iterator_to_array($node->attributes) as $attribute) {
                    $node->removeAttributeNode($attribute);
                }
            }
        }

        foreach (iterator_to_array($node->childNodes) as $child) {
            $this->sanitizeNode($child);
        }
    }

    private function unwrapNode(\DOMElement $node): void
    {
        $parent = $node->parentNode;
        if ($parent === null) {
            return;
        }

        while ($node->firstChild !== null) {
            $parent->insertBefore($node->firstChild, $node);
        }

        $parent->removeChild($node);
    }

    private function isAllowedHref(string $href): bool
    {
        $trimmed = trim($href);
        if ($trimmed === '') {
            return false;
        }

        if ($this->startsWith($trimmed, '/')) {
            return true;
        }

        $scheme = strtolower((string)parse_url($trimmed, PHP_URL_SCHEME));
        return $scheme === 'http' || $scheme === 'https';
    }

    private function isExternalHref(string $href): bool
    {
        $scheme = strtolower((string)parse_url($href, PHP_URL_SCHEME));
        return $scheme === 'http' || $scheme === 'https';
    }

    private function startsWith(string $haystack, string $needle): bool
    {
        return strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}
