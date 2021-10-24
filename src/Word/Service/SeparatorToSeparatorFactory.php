<?php

declare(strict_types=1);

namespace Laminas\Filter\Word\Service;

use Interop\Container\ContainerInterface;
use Laminas\Filter\Word\SeparatorToSeparator;
use Laminas\ServiceManager\Exception\InvalidServiceException;
use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Traversable;

use function gettype;
use function is_array;
use function is_object;
use function iterator_to_array;
use function sprintf;

class SeparatorToSeparatorFactory implements FactoryInterface
{
    /**
     * Options to pass to the constructor (when used in v2), if any.
     *
     * @var array<int|string, mixed>
     */
    private array $creationOptions;

    /**
     * @param iterable<int|string, mixed>|null $creationOptions
     */
    public function __construct(?array $creationOptions = null)
    {
        if (null === $creationOptions) {
            return;
        }

        if ($creationOptions instanceof Traversable) {
            $creationOptions = iterator_to_array($creationOptions);
        }

        if (! is_array($creationOptions)) {
            throw new InvalidServiceException(sprintf(
                '%s cannot use non-array, non-traversable creation options; received %s',
                self::class,
                is_object($creationOptions) ? get_class($creationOptions) : gettype($creationOptions)
            ));
        }

        $this->creationOptions = $creationOptions;
    }

    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null
    ): SeparatorToSeparator {
        return new SeparatorToSeparator(
            $options['search_separator'] ?? ' ',
            $options['replacement_separator'] ?? '-'
        );
    }

    /**
     * @return SeparatorToSeparator
     */
    public function createService(ServiceLocatorInterface $serviceLocator): SeparatorToSeparator
    {
        return $this($serviceLocator, self::class, $this->creationOptions ?? null);
    }

    /**
     * @param array<int|string, mixed> $options
     *
     * @return void
     */
    public function setCreationOptions(array $options): void
    {
        $this->creationOptions = $options;
    }
}
