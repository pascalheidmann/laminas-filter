<?php

declare(strict_types=1);

namespace LaminasTest\Filter\TestAsset;

use Laminas\Filter\AbstractFilter;

use function strtolower;

class LowerCase extends AbstractFilter
{
    /**
     * @param mixed $value
     */
    public function filter($value): string
    {
        return strtolower($value);
    }
}
