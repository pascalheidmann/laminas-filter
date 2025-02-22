<?php

declare(strict_types=1);

namespace Laminas\Filter;

use Laminas\ModuleManager\ModuleManager;

class Module
{
    /**
     * Return default laminas-filter configuration for laminas-mvc applications.
     *
     * @return array{service_manager: string[]}
     */
    public function getConfig(): array
    {
        $provider = new ConfigProvider();

        return [
            'service_manager' => $provider->getDependencyConfig(),
        ];
    }

    /**
     * Register a specification for the FilterManager with the ServiceListener.
     *
     * @param ModuleManager $moduleManager
     * @return void
     */
    public function init($moduleManager)
    {
        $event           = $moduleManager->getEvent();
        $container       = $event->getParam('ServiceManager');
        $serviceListener = $container->get('ServiceListener');

        $serviceListener->addServiceManager(
            'FilterManager',
            'filters',
            FilterProviderInterface::class,
            'getFilterConfig'
        );
    }
}
