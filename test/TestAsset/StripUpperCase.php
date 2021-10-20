<?php

declare(strict_types=1);

namespace LaminasTest\Filter\TestAsset;

use Laminas\Filter\AbstractFilter;

use function preg_replace;

class StripUpperCase extends AbstractFilter
{
    /**
     * @param mixed $value
     * @return array|string|string[]|null
     */
    public function filter($value)
    {
        return preg_replace('/[A-Z]/', '', $value);
    }
}
