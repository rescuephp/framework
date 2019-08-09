<?php

declare(strict_types=1);

namespace Rescue\Kernel;

use ReflectionException;
use Rescue\Container\ContainerInterface;

class Bootstrap
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var string[]
     */
    private $loaders;

    /**
     * @var array
     */
    private $defaultClasses;

    /**
     * @var string[]
     */
    private $middlewaresBefore;

    /**
     * @var string[]
     */
    private $middlewaresAfter;

    /**
     * Bootstrap constructor.
     * @param ContainerInterface $container
     * @param string[] $loaders
     * @param array $defaultClasses
     * @param string[] $middlewaresBefore
     * @param string[] $middlewaresAfter
     */
    public function __construct(
        ContainerInterface $container,
        array $loaders,
        array $defaultClasses,
        array $middlewaresBefore = [],
        array $middlewaresAfter = []
    ) {
        $this->container = $container;
        $this->container->addInstance(ContainerInterface::class, $this->container);
        $this->loaders = $loaders;
        $this->defaultClasses = $defaultClasses;
        $this->middlewaresBefore = $middlewaresBefore;
        $this->middlewaresAfter = $middlewaresAfter;
    }

    public function getMiddlewaresBefore(): array
    {
        return $this->middlewaresBefore;
    }

    public function getMiddlewaresAfter(): array
    {
        return $this->middlewaresAfter;
    }

    /**
     * @throws ReflectionException
     */
    public function bootstrap(): void
    {
        $this->container->addInstance(__CLASS__, $this);
        $this->registerDefault();
        $this->dispatchLoaders();
    }

    /**
     * @throws ReflectionException
     */
    private function registerDefault(): void
    {
        foreach ($this->defaultClasses as $id => $className) {
            $this->container->add($id, $className);
        }
    }

    /**
     * @throws ReflectionException
     */
    private function dispatchLoaders(): void
    {
        foreach ($this->loaders as $loaderClass) {
            /** @var LoaderInterface $loader */
            $loader = $this->container->add($loaderClass);
            $loader->load();
        }
    }

}
