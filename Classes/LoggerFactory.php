<?php
namespace Flowpack\Monolog;

use Monolog\Handler\FormattableHandlerInterface;
use Monolog\Handler\HandlerInterface;
use Neos\Flow\Log\PsrLoggerFactoryInterface;
use Neos\Flow\Annotations as Flow;
use Monolog\Logger;
use Neos\Flow\Configuration\Exception\InvalidConfigurationException;
use Neos\Utility\PositionalArraySorter;

/**
 * Class LoggerFactory
 *
 * @Flow\Proxy(false)
 */
class LoggerFactory implements PsrLoggerFactoryInterface
{
    /**
     * @var LoggerFactory
     */
    private static $instance;

    protected array $loggerInstances = [];

    protected array $handlerInstances = [];

    protected array $configuration;

    /**
     * @Flow\Autowiring(false)
     */
    public function __construct(array $configuration = [])
    {
        $this->configuration = $configuration;
    }

    /**
     * @return LoggerFactory|static
     */
    public static function create(array $configuration)
    {
        return new self($configuration);
    }

    /**
     * @return Logger|\Psr\Log\LoggerInterface
     */
    public function get(string $identifier)
    {
        $configuration = $this->configuration[$identifier];
        return $this->createFromConfiguration($identifier, $configuration);
    }

    public function injectConfiguration(array $configuration): void
    {
        $this->configuration = $configuration;
    }

    /**
     * Creates a monolog instance.
     */
    public function createFromConfiguration(string $identifier, array $configuration): Logger
    {
        if (isset($this->loggerInstances[$identifier])) {
            return $this->loggerInstances[$identifier];
        }

        $logger = new Logger($identifier);

        $handlerSorter = new PositionalArraySorter($configuration['handler']);
        foreach ($handlerSorter->toArray() as $handlerConfiguration) {
            $handler = null;
            if (is_string($handlerConfiguration)) {
                $handler = $this->getConfiguredHandler($handlerConfiguration);
            }

            if (is_array($handlerConfiguration)) {
                $handlerIdentifier = $identifier . md5(json_encode($handlerConfiguration, JSON_THROW_ON_ERROR));
                $handler = $this->instanciateHandler($handlerIdentifier, $handlerConfiguration);
            }

            if ($handler !== null) {
                $logger->pushHandler($handler);
            }
        }

        $this->loggerInstances[$identifier] = $logger;

        return $logger;
    }

    /**
     * @throws InvalidConfigurationException
     * @api
     */
    public function getConfiguredHandler(string $identifier): HandlerInterface
    {
        if (!isset($this->configuration['handler'][$identifier])) {
            throw new InvalidConfigurationException(sprintf('The required handler configuration for the given identifier "%s" was not found. Please configure a logger with this identifier in "Flowpack.Monolog.handler"', $identifier), 1436767040);
        }

        return $this->instanciateHandler($identifier, $this->configuration['handler'][$identifier]);
    }

    /**
     * Home brew singleton because it is used so early.
     */
    public static function getInstance(): LoggerFactory
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * @throws InvalidConfigurationException
     */
    protected function instanciateHandler(string $identifier, array $handlerConfiguration): HandlerInterface
    {
        if (!isset($this->handlerInstances[$identifier])) {
            $handlerClass = $handlerConfiguration['className'] ?? false;

            if (!class_exists($handlerClass)) {
                throw new InvalidConfigurationException(sprintf('The given handler class "%s" does not exist, please check configuration for handler "%s".', $handlerClass, $identifier), 1436767219);
            }

            $arguments = (isset($handlerConfiguration['arguments']) && is_array($handlerConfiguration['arguments'])) ? $handlerConfiguration['arguments'] : [];
            $handler = new $handlerClass(...$arguments);

            $formatterClass = $handlerConfiguration['formatterClassName'] ?? false;
            if ($formatterClass) {
                if (!class_exists($formatterClass)) {
                    throw new InvalidConfigurationException(sprintf('The given formatter class "%s" does not exist, please check configuration for handler "%s".', $formatterClass, $identifier), 1702638212);
                }
                if (!$handler instanceof FormattableHandlerInterface) {
                    throw new InvalidConfigurationException(sprintf('The given handler class "%s" does not implement the FormattableHandlerInterface, please check configuration for handler "%s".', $handlerClass, $identifier), 1702638379);
                }

                $arguments = (isset($handlerConfiguration['formatterArguments']) && is_array($handlerConfiguration['formatterArguments'])) ? $handlerConfiguration['formatterArguments'] : [];
                $formatter = new $formatterClass(...$arguments);
                $handler->setFormatter($formatter);
            }

            $this->handlerInstances[$identifier] = $handler;
        }

        return $this->handlerInstances[$identifier];
    }
}
