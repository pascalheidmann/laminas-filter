<?php

declare(strict_types=1);

namespace Laminas\Filter\Word;

use Laminas\Stdlib\StringUtils;

use function extension_loaded;
use function is_array;
use function is_scalar;
use function preg_quote;
use function preg_replace_callback;

class SeparatorToCamelCase extends AbstractSeparator
{
    /**
     * Defined by Laminas\Filter\Filter
     *
     * @param  string|array $value
     * @return string|array
     */
    public function filter($value)
    {
        if (! is_scalar($value) && ! is_array($value)) {
            return $value;
        }

        // a unicode safe way of converting characters to \x00\x00 notation
        $pregQuotedSeparator = preg_quote($this->separator, '#');

        if (StringUtils::hasPcreUnicodeSupport()) {
            $patterns = [
                '#(' . $pregQuotedSeparator . ')(\P{Z}{1})#u',
                '#(^\P{Z}{1})#u',
            ];
            if (! extension_loaded('mbstring')) {
                $replacements = [
                    // @codingStandardsIgnoreStart
                    static function ($matches): string {
                        return strtoupper($matches[2]);
                    },
                    static function ($matches): string {
                        return strtoupper($matches[1]);
                    },
                    // @codingStandardsIgnoreEnd
                ];
            } else {
                $replacements = [
                    // @codingStandardsIgnoreStart
                    static function ($matches): string {
                        return mb_strtoupper($matches[2], 'UTF-8');
                    },
                    static function ($matches): string {
                        return mb_strtoupper($matches[1], 'UTF-8');
                    },
                    // @codingStandardsIgnoreEnd
                ];
            }
        } else {
            $patterns     = [
                '#(' . $pregQuotedSeparator . ')([\S]{1})#',
                '#(^[\S]{1})#',
            ];
            $replacements = [
                // @codingStandardsIgnoreStart
                static function ($matches): string {
                    return strtoupper($matches[2]);
                },
                static function ($matches): string {
                    return strtoupper($matches[1]);
                },
                // @codingStandardsIgnoreEnd
            ];
        }

        $filtered = $value;
        foreach ($patterns as $index => $pattern) {
            $filtered = preg_replace_callback($pattern, $replacements[$index], $filtered);
        }
        return $filtered;
    }
}
