<?php

declare(strict_types=1);

namespace Laminas\Filter;

use Traversable;

use function func_get_args;
use function gettype;
use function is_array;
use function is_object;
use function is_scalar;
use function is_string;
use function iterator_to_array;
use function preg_match;
use function preg_replace;
use function sprintf;
use function strpos;

class PregReplace extends AbstractFilter
{
    /** @var array{pattern: string, replacement: string}  */
    protected $options = [
        'pattern'     => null,
        'replacement' => '',
    ];

    /**
     * Constructor
     * Supported options are
     *     'pattern'     => matching pattern
     *     'replacement' => replace with this
     *
     * @param  array|Traversable|string|null $options
     */
    public function __construct($options = null)
    {
        if ($options instanceof Traversable) {
            $options = iterator_to_array($options);
        }

        if (! is_array($options) || (! isset($options['pattern']) && ! isset($options['replacement']))) {
            $args = func_get_args();
            if (isset($args[0])) {
                $this->setPattern($args[0]);
            }
            if (isset($args[1])) {
                $this->setReplacement($args[1]);
            }
        } else {
            $this->setOptions($options);
        }
    }

    /**
     * Set the regex pattern to search for
     *
     * @see preg_replace()
     *
     * @param  string|array $pattern - same as the first argument of preg_replace
     * @return self
     * @throws Exception\InvalidArgumentException
     */
    public function setPattern($pattern)
    {
        if (! is_array($pattern) && ! is_string($pattern)) {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s expects pattern to be array or string; received "%s"',
                __METHOD__,
                is_object($pattern) ? get_class($pattern) : gettype($pattern)
            ));
        }

        if (is_array($pattern)) {
            foreach ($pattern as $p) {
                $this->validatePattern($p);
            }
        }

        if (is_string($pattern)) {
            $this->validatePattern($pattern);
        }

        $this->options['pattern'] = $pattern;
        return $this;
    }

    /**
     * Get currently set match pattern
     *
     * @return string|array
     */
    public function getPattern()
    {
        return $this->options['pattern'];
    }

    /**
     * Set the replacement array/string
     *
     * @see preg_replace()
     *
     * @param  array|string $replacement - same as the second argument of preg_replace
     * @return self
     * @throws Exception\InvalidArgumentException
     */
    public function setReplacement($replacement)
    {
        if (! is_array($replacement) && ! is_string($replacement)) {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s expects replacement to be array or string; received "%s"',
                __METHOD__,
                is_object($replacement) ? get_class($replacement) : gettype($replacement)
            ));
        }
        $this->options['replacement'] = $replacement;
        return $this;
    }

    /**
     * Get currently set replacement value
     *
     * @return string|array
     */
    public function getReplacement()
    {
        return $this->options['replacement'];
    }

    /**
     * Perform regexp replacement as filter
     *
     * @param  mixed $value
     * @return mixed
     * @throws Exception\RuntimeException
     */
    public function filter($value)
    {
        if (! is_scalar($value) && ! is_array($value)) {
            return $value;
        }

        if ($this->options['pattern'] === null) {
            throw new Exception\RuntimeException(sprintf(
                'Filter %s does not have a valid pattern set',
                static::class
            ));
        }

        return preg_replace($this->options['pattern'], $this->options['replacement'], $value);
    }

    /**
     * Validate a pattern and ensure it does not contain the "e" modifier
     *
     * @param string $pattern
     *
     * @return null|true
     *
     * @throws Exception\InvalidArgumentException
     */
    protected function validatePattern($pattern): ?bool
    {
        if (! preg_match('/(?<modifier>[imsxeADSUXJu]+)$/', $pattern, $matches)) {
            return true;
        }

        if (false !== strpos($matches['modifier'], 'e')) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Pattern for a PregReplace filter may not contain the "e" pattern modifier; received "%s"',
                $pattern
            ));
        }
    }
}
