<?php

namespace App\Services;

class LetterTemplateHtmlSanitizer
{
    /** @var array<int, string> */
    private array $allowedTags = [
        'p', 'div', 'br', 'strong', 'b', 'em', 'i', 'u', 's',
        'ul', 'ol', 'li', 'blockquote',
        'table', 'thead', 'tbody', 'tfoot', 'tr', 'th', 'td',
        'hr', 'span',
    ];

    public function sanitize(?string $html): string
    {
        $html = trim((string) $html);

        if ($html === '') {
            return '';
        }

        if (! preg_match('/<\/?[a-z][^>]*>/i', $html)) {
            return $this->plainTextToHtml($html);
        }

        // Buang isi tag berbahaya, bukan hanya tag pembungkusnya.
        $html = preg_replace(
            '#<(script|style|iframe|object|embed)\b[^>]*>.*?</\1>#is',
            '',
            $html
        ) ?? $html;

        $allowed = '<'.implode('><', $this->allowedTags).'>';
        $html = strip_tags($html, $allowed);

        // Rebuild opening tags agar hanya atribut yang memang dibutuhkan editor
        // yang lolos ke database/PDF.
        $html = preg_replace_callback(
            '/<([a-z0-9]+)(\s[^>]*)?>/i',
            function (array $match): string {
                $tag = strtolower($match[1]);
                $attributes = $match[2] ?? '';

                if (! in_array($tag, $this->allowedTags, true)) {
                    return '';
                }

                $safeAttributes = [];

                if (preg_match('/\bstyle\s*=\s*(["\'])(.*?)\1/is', $attributes, $styleMatch)) {
                    $style = $this->sanitizeStyle($styleMatch[2]);
                    if ($style !== '') {
                        $safeAttributes[] = 'style="'.htmlspecialchars($style, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'"';
                    }
                }

                if (in_array($tag, ['td', 'th'], true)) {
                    foreach (['colspan', 'rowspan'] as $name) {
                        if (preg_match('/\b'.$name.'\s*=\s*(["\']?)(\d+)\1/i', $attributes, $valueMatch)) {
                            $number = max(1, min(20, (int) $valueMatch[2]));
                            $safeAttributes[] = $name.'="'.$number.'"';
                        }
                    }
                }

                return '<'.$tag.($safeAttributes ? ' '.implode(' ', $safeAttributes) : '').'>';
            },
            $html
        ) ?? $html;

        return trim($html);
    }

    public function forPdf(?string $html, bool $stripLegacyRecipient = false): string
    {
        $safe = $this->sanitize($html);

        if ($safe === '') {
            return '';
        }

        // Hilangkan blok kosong di awal.
        $safe = preg_replace('#\A(?:\s|<br\s*/?>|<(?:p|div)\b[^>]*>\s*(?:<br\s*/?>|&nbsp;)?\s*</(?:p|div)>)+#iu', '', $safe) ?? $safe;

        if ($stripLegacyRecipient) {
            // Hanya untuk template lama yang masih memakai layout PDF legacy.
            $safe = preg_replace(
                '#\A\s*<(p|div)\b[^>]*>\s*Yth\.?\s*.*?</\1>\s*(?:<(p|div)\b[^>]*>\s*(?:di|Di)\b.*?</\2>\s*)?#isu',
                '',
                $safe
            ) ?? $safe;
        }

        $safe = preg_replace('#:\s*\.(\s*</(?:p|div)>)#iu', ':$1', $safe) ?? $safe;
        $safe = preg_replace('#:\s*\.(\s*<br\s*/?>)#iu', ':$1', $safe) ?? $safe;

        return trim($safe);
    }

    public function escapePlaceholderValue(mixed $value): string
    {
        $escaped = htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return nl2br($escaped, false);
    }

    private function sanitizeStyle(string $style): string
    {
        $safe = [];

        foreach (explode(';', $style) as $rule) {
            if (! str_contains($rule, ':')) {
                continue;
            }

            [$property, $value] = array_map('trim', explode(':', $rule, 2));
            $property = strtolower($property);
            $value = strtolower($value);

            if ($property === 'text-align' && in_array($value, ['left', 'center', 'right', 'justify'], true)) {
                $safe[] = 'text-align: '.$value;

                continue;
            }

            if ($property === 'vertical-align' && in_array($value, ['top', 'middle', 'bottom'], true)) {
                $safe[] = 'vertical-align: '.$value;

                continue;
            }

            if (in_array($property, ['width', 'max-width'], true)) {
                if ($value === 'auto') {
                    $safe[] = $property.': auto';

                    continue;
                }

                if (preg_match('/^\d{1,3}(?:\.\d{1,2})?%$/', $value) === 1) {
                    $number = (float) rtrim($value, '%');

                    if ($number >= 1 && $number <= 100) {
                        $safe[] = $property.': '.rtrim(rtrim(number_format($number, 2, '.', ''), '0'), '.').'%';
                    }
                }

                continue;
            }

            if ($property === 'border') {
                if (in_array($value, ['0', 'none'], true)) {
                    $safe[] = 'border: 0';

                    continue;
                }

                if (preg_match('/^(?:0\.[1-9]|[1-2](?:\.\d+)?)pt\s+(solid|dashed|dotted)\s+(#000|#111|black)$/', $value, $borderMatch) === 1) {
                    $safe[] = 'border: '.preg_replace('/\s+/', ' ', $value);
                }

                continue;
            }
        }

        return implode('; ', $safe);
    }

    private function plainTextToHtml(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", trim($text));
        $paragraphs = preg_split('/\n{2,}/', $text) ?: [];

        return implode('', array_map(function (string $paragraph): string {
            $escaped = htmlspecialchars(trim($paragraph), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

            return '<p>'.nl2br($escaped, false).'</p>';
        }, $paragraphs));
    }
}
