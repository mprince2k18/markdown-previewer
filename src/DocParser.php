<?php
/**
 * File containing the class {@see \Mprince\MarkdownViewer\DocParser}.
 *
 * @package MarkdownViewer
 * @see \Mprince\MarkdownViewer\DocParser
 */

declare(strict_types=1);

namespace Mprince\MarkdownViewer;

use AppUtils\FileHelper;
use GeSHi;
use ParsedownExtra;

/**
 * Markdown document parser. Uses "ParseDown extra" to parse the
 * document, and adds some of its own functionality into the mix.
 *
 * @package MarkdownViewer
 * @author Mohammad Prince <mprince2k16@gmail.com>
 */
class DocParser
{
    public const OVERALL_CLASS = 'geshifilter';

    private string $html;
    private DocFile $file;

    /**
     * @var DocHeader[]
     */
    private array $headers = array();

    /**
     * Aliases for code fence language names.
     *
     * @var array<string,string>
     */
    private $langAliases = array(
        'js' => 'javascript',
        'html' => 'html5',
        'json' => 'javascript'
    );

    public function __construct(DocFile $file)
    {
        $parse = new ParsedownExtra();

        $text = FileHelper::readContents($file->getPath());
        $text = $this->parseListStyles($text);

        $this->file = $file;
        $this->html = (string)$parse->text($text);

        $this->parseHeaders();
        $this->parseCode();
    }

    /**
     * @return DocHeader[]
     */
    public function getHeaders() : array
    {
        return $this->headers;
    }

    /**
     * Replace all list items in the text that use the "1)" notation
     * with the markdown "1." style, so it can be detected correctly.
     *
     * @param string $text
     * @return string
     */
    private function parseListStyles(string $text) : string
    {
        $lines = explode("\n", $text);

        $keep = array();
        foreach ($lines as $line)
        {
            $pos = strpos($line, ')');

            if($pos !== false) {
                $line = $this->checkBullet($line, $pos);
            }

            $keep[] = $line;
        }

        return implode("\n", $keep);
    }

    private function checkBullet(string $line, int $pos) : string
    {
        $sub = substr($line, 0, $pos);
        $trimmed = trim($sub);

        if(is_numeric($trimmed)) {
            return substr_replace($line,$trimmed.'.', 0, $pos+1);
        }

        return $line;
    }

    /**
     * It finds all the headers in the document, creates a DocHeader object for each one, and then
     * replaces the header in the document with a link to the header
     */
    private function parseHeaders() : void
    {
        /* Using a regular expression to find all the headers in the document. */
        preg_match_all('%<h([0-9])\b[^>]*>(.*?)</h[0-9]>%si', $this->html, $result, PREG_PATTERN_ORDER);

        $active = array();
        $headers = array();

        /* Parsing the headers of a document. */
        foreach($result[2] as $idx => $title)
        {
            /* Creating a new DocHeader object. */
            $header = new DocHeader($title, (int)$result[1][$idx], $result[0][$idx]);

            /* Getting the level of the header. */
            $level = $header->getLevel();

            $headers[] = $header;
            $active[$level] = $header;

            /* Adding the header to the headers array. */
            if($level === 1) {
                $this->headers[] = $header;
                continue;
            }

            $prevIndex = ($level-1);

            if(isset($active[$prevIndex]))
            {
                $active[$prevIndex]->addSubheader($header);
            }
        }

        foreach ($headers as $header)
        {
            $this->html = $header->replace($this->html, $this->file);
        }
    }

    private function parseCode() : void
    {
        preg_match_all('%<code class="language-([a-z]+)">(.*?)</code>%si', $this->html, $result, PREG_PATTERN_ORDER);

        foreach($result[2] as $idx => $matchedText)
        {
            $this->html = str_replace($matchedText, $this->renderCode($matchedText, $this->resolveLanguage($result[1][$idx])), $this->html);
        }
    }

    private function resolveLanguage(string $lang) : string
    {
        return $this->langAliases[$lang] ?? $lang;
    }

    private function renderCode(string $code, string $language) : string
    {
        $code = html_entity_decode($code);

        $geshi = new GeSHi($code, $language);
        $geshi->set_overall_class(self::OVERALL_CLASS);
        $geshi->enable_classes();
        $geshi->set_methods_highlighting(true);
        $geshi->set_numbers_highlighting(true);
        $geshi->set_symbols_highlighting(true);
        $geshi->set_strings_highlighting(true);
        $high = $geshi->parse_code();

        return str_replace(
            array(
                sprintf('<pre class="%s %s">', $language, self::OVERALL_CLASS),
                '</pre>'
            ),
            '',
            $high
        );
    }

    public function render() : string
    {
        return $this->html;
    }
}
