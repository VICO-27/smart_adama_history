<?php

namespace App\Services;

use Parsedown;
use HTMLPurifier;
use HTMLPurifier_Config;

/**
 * Markdown processing service with HTML sanitization.
 *
 * Converts markdown to sanitized HTML suitable for display in Vue components.
 * Uses Parsedown for markdown parsing and HTMLPurifier for XSS protection.
 */
class MarkdownService
{
    private ?Parsedown $parsedown = null;
    private ?HTMLPurifier $purifier = null;

    public function __construct()
    {
        $this->parsedown = new Parsedown();
        $this->purifier = new HTMLPurifier($this->getPurifierConfig());
    }

    /**
     * Convert markdown to sanitized HTML.
     *
     * @param string $markdown The markdown text to convert
     * @return string The sanitized HTML
     */
    public function toHtml(string $markdown): string
    {
        if (empty(trim($markdown))) {
            return '';
        }

        // Parse markdown to HTML
        $html = $this->parsedown->text($markdown);

        // Sanitize HTML to prevent XSS
        $sanitized = $this->purifier->purify($html);

        return $sanitized;
    }

    /**
     * Get HTMLPurifier configuration.
     */
    private function getPurifierConfig(): HTMLPurifier_Config
    {
        $config = HTMLPurifier_Config::createDefault();

        // Allow common HTML elements and attributes
        $config->set('HTML.Allowed', 'p,br,hr,strong,em,b,i,u,a,h1,h2,h3,h4,h5,h6,ul,ol,li,blockquote,cite,code,pre,table,thead,tbody,tr,th,td,caption,sub,sup');

        // Allow safe attributes
        $config->set('Attr.AllowedClasses', []);

        // Enable inline styles for code blocks (syntax highlighting classes)
        $config->set('CSS.AllowImportant', false);
        $config->set('CSS.Proprietary', false);

        // Allow safe protocols
        $config->set('URI.AllowedSchemes', [
            'http' => true,
            'https' => true,
            'mailto' => true,
        ]);

        // Prevent iframe/embed (no remote content)
        $config->set('HTML.SafeEmbed', false);
        $config->set('HTML.SafeObject', false);

        // Output xHTML
        $config->set('HTML.XHTML', true);

        return $config;
    }
}
