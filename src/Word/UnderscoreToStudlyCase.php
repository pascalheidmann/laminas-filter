<?php

declare(strict_types=1);

namespace Laminas\Filter\Word;

use Laminas\Stdlib\StringUtils;

use function array_map;
use function extension_loaded;
use function is_array;
use function is_scalar;
use function mb_strlen;
use function mb_strtolower;
use function mb_substr;

class UnderscoreToStudlyCase extends UnderscoreToCamelCase
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

        $value          = parent::filter($value);
        $lowerCaseFirst = 'lcfirst';

        if (StringUtils::hasPcreUnicodeSupport() && extension_loaded('mbstring')) {
            $lowerCaseFirst = static function ($value) {
                if (0 === mb_strlen($value)) {
                    return $value;
                }

                return mb_strtolower(mb_substr($value, 0, 1)) . mb_substr($value, 1);
            };
        }

        return is_array($value) ? array_map($lowerCaseFirst, $value) : $lowerCaseFirst($value);
    }
}
