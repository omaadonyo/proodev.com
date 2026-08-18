<?php

namespace App\Support;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\MarkdownConverter;

/**
 * Renders untrusted markdown (repo READMEs, AI drafts) to safe HTML.
 *
 * Raw HTML is stripped from the source before parsing — rather than relying
 * on CommonMark's html_input=strip — so that a line-start HTML block from a
 * repo README (e.g. `<p align="left">…`) keeps its text content instead of
 * being dropped entirely, and no raw markup or script can ever survive into
 * the output. Autolinking, tables, strikethrough and task lists come from the
 * GitHub-flavored extension that ships with league/commonmark (already
 * required by the framework).
 */
class Markdown
{
    private static ?MarkdownConverter $converter = null;

    public static function render(?string $markdown): string
    {
        if (! $markdown || trim($markdown) === '') {
            return '';
        }

        $markdown = (string) preg_replace('/\r\n?/', "\n", $markdown);

        // Remove raw HTML tags and comments from the source first. The text
        // between tags is preserved; only the markup itself is dropped.
        $markdown = (string) preg_replace(
            [
                '/<!--.*?-->/s',
                '/<\/?[a-zA-Z][^>]*>/',
            ],
            '',
            $markdown
        );

        return self::converter()->convert($markdown)->getContent();
    }

    /**
     * Render markdown and reduce it to clean plain text — used for short
     * previews (taglines, card excerpts) where a full render would leak raw
     * markup or HTML into a single-line element.
     */
    public static function plain(?string $markdown): string
    {
        if (! $markdown || trim($markdown) === '') {
            return '';
        }

        $html = self::render($markdown);
        $plain = (string) preg_replace('/<[^>]+>/', ' ', $html);

        return trim((string) preg_replace(
            '/\s+/u',
            ' ',
            html_entity_decode($plain, ENT_QUOTES | ENT_HTML5, 'UTF-8')
        ));
    }

    private static function converter(): MarkdownConverter
    {
        if (self::$converter === null) {
            $environment = new Environment([
                'html_input' => 'strip',
                'allow_unsafe_links' => false,
                'max_nesting_level' => 20,
            ]);

            $environment->addExtension(new CommonMarkCoreExtension);
            $environment->addExtension(new GithubFlavoredMarkdownExtension);

            self::$converter = new MarkdownConverter($environment);
        }

        return self::$converter;
    }
}
