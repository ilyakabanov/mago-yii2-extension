<?php

declare(strict_types=1);

namespace yii2\extensions\mago;

use Closure;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionNamedType;
use RuntimeException;
use yii\base\InvalidArgumentException;
use yii\di\Instance;
use yii\web\Application;

/**
 * Normalizes Yii configuration using the component and container resolution rules.
 *
 * Adapted from yii2-extensions/phpstan ServiceMap; see LICENSE for attribution.
 * Config files are executable PHP and must be trusted. Factories are reflected, never invoked.
 */
final class ServiceMap
{
    private const CLASS_NAME_PATTERN = '/^[a-zA-Z_\x80-\xff][\w\x80-\xff]*(?:\\\\[a-zA-Z_\x80-\xff][\w\x80-\xff]*)*$/';
    private const CONTAINER_SECTIONS = ['definitions' => 'Definition', 'singletons' => 'Singleton'];
    private const RELATIVE_CLASS_TYPES = ['parent', 'self', 'static'];

    private string $applicationType = '';
    /** @var array<string, list<string>> */
    private array $behaviors = [];
    /** @var array<string, string> */
    private array $components = [];
    /** @var array<string, array<array-key, mixed>> */
    private array $componentsDefinitions = [];
    /** @var array<string, true> */
    private array $componentsWithoutClass = [];
    /** @var array<string, true> */
    private array $objectDefinedComponents = [];
    /** @var array<string, true> */
    private array $objectDefinedServices = [];
    /** @var array<array-key, mixed> */
    private array $params = [];
    /** @var array<string, string> */
    private array $services = [];
    /** @var array<string, true> */
    private array $unresolvedComponents = [];
    /** @var array<string, true> */
    private array $unresolvedServices = [];

    public function __construct(string $configPath = '')
    {
        if ($configPath !== '') {
            $resolvedPath = realpath($configPath);
            if (
                $resolvedPath === false
                || !is_file($resolvedPath)
                || !is_readable($resolvedPath)
                || pathinfo($resolvedPath, PATHINFO_EXTENSION) !== 'php'
            ) {
                throw new InvalidArgumentException(sprintf(
                    "Provided config path '%s' must be a readable PHP file.",
                    $configPath,
                ));
            }
            $configPath = $resolvedPath;
        }

        // Yii's Composer autoloader intentionally does not bootstrap its global facade.
        if (!class_exists('Yii', false)) {
            $applicationFile = (new ReflectionClass(Application::class))->getFileName();
            if ($applicationFile === false) {
                throw new RuntimeException('Cannot locate the Yii application source file.');
            }
            $yiiDirectory = dirname($applicationFile, 2);
            require_once $yiiDirectory . '/Yii.php';
        }

        $config = $this->loadConfig($configPath);
        $this->applicationType = $config['applicationType'];
        $this->params = $config['params'];
        $this->processBehaviors($config['behaviors']);
        $this->processComponents($config['components']);
        foreach ($config['containerSections'] as [$label, $services]) {
            $this->processServices($services, $label);
        }
        $this->resolveContainerAliases();
    }

    public function getApplicationType(): string
    {
        return $this->applicationType;
    }

    /** @return list<string> */
    public function getBehaviorsByClassName(string $class): array
    {
        return $this->behaviors[$class] ?? [];
    }

    public function getComponentClassById(string $id): ?string
    {
        return $this->components[$id] ?? null;
    }

    /** @return array<array-key, mixed> */
    public function getComponentDefinitionById(string $id): array
    {
        return $this->componentsDefinitions[$id] ?? [];
    }

    /** @return array<array-key, mixed> */
    public function getParams(): array
    {
        return $this->params;
    }

    public function getServiceById(string $id): ?string
    {
        return $this->services[$id] ?? null;
    }

    public function isComponentWithoutClass(string $id): bool
    {
        return isset($this->componentsWithoutClass[$id]);
    }

    public function isUnresolvedComponent(string $id): bool
    {
        return isset($this->unresolvedComponents[$id]);
    }

    public function isUnresolvedService(string $id): bool
    {
        return isset($this->unresolvedServices[$id]);
    }

    /** @param array<string, true> $visited */
    private function followContainerAliases(string $className, array $visited): ?string
    {
        while (isset($this->services[$className]) && $this->services[$className] !== $className) {
            if (isset($visited[$className])) {
                return null;
            }
            if (isset($this->objectDefinedServices[$className])) {
                return $this->services[$className];
            }
            $visited[$className] = true;
            $className = $this->services[$className];
        }
        return isset($this->unresolvedServices[$className]) ? null : $className;
    }

    /**
     * @return array{
     *     applicationType: string,
     *     behaviors: array<array-key, mixed>,
     *     components: array<array-key, mixed>,
     *     containerSections: list<array{string, array<array-key, mixed>}>,
     *     params: array<array-key, mixed>,
     * }
     */
    private function loadConfig(string $configPath): array
    {
        $config = $configPath !== '' ? (require $configPath) : [];
        if (!is_array($config)) {
            throw new RuntimeException(sprintf("Configuration file '%s' must return an array.", $configPath));
        }

        $legacy = $this->section($config, 'phpstan', $configPath, 'phpstan');
        $mago = $this->section($config, 'mago', $configPath, 'mago');
        $applicationKey = isset($mago['application_type']) ? 'mago.application_type' : 'phpstan.application_type';
        $applicationType = $mago['application_type'] ?? $legacy['application_type'] ?? '';
        if (!is_string($applicationType)) {
            $this->throwErrorWhenIsNotString('Application type', $applicationKey, gettype($applicationType));
        }

        $behaviors = $this->section($config, 'behaviors', $configPath, 'behaviors');
        $components = $this->section($config, 'components', $configPath, 'components');
        $params = array_key_exists('params', $config) ? $config['params'] : [];
        if (!is_array($params)) {
            $this->throwErrorWhenConfigFileIsNotArray($configPath, 'params');
        }

        $container = $this->section($config, 'container', $configPath, 'container');
        $containerSections = [];
        // Yii::configure applies definitions/singletons in insertion order; the last entry wins.
        foreach (array_keys($container) as $key) {
            if (is_string($key) && isset(self::CONTAINER_SECTIONS[$key])) {
                $containerSections[] = [
                    self::CONTAINER_SECTIONS[$key],
                    $this->section($container, $key, $configPath, 'container.' . $key),
                ];
            }
        }
        return [
            'applicationType' => $applicationType !== '' ? $applicationType : Application::class,
            'behaviors' => $behaviors,
            'components' => $components,
            'containerSections' => $containerSections,
            'params' => $params,
        ];
    }

    private function normalizeClassName(string $class): ?string
    {
        $class = ltrim($class, '\\');
        return $class !== '' ? $class : null;
    }

    /** @param array<array-key, mixed> $behaviors */
    private function processBehaviors(array $behaviors): void
    {
        foreach ($behaviors as $id => $definition) {
            if (!is_string($id)) {
                $this->throwErrorWhenIsNotString('Behavior class', 'ID', gettype($id));
            }
            if (!is_array($definition)) {
                throw new RuntimeException(sprintf("Behavior definition for '%s' must be an array.", $id));
            }
            $this->behaviors[$id] = array_values(array_filter($definition, is_string(...)));
        }
    }

    /** @param array<array-key, mixed> $components */
    private function processComponents(array $components): void
    {
        foreach ($components as $id => $definition) {
            if (!is_string($id)) {
                $this->throwErrorWhenIsNotString('Component', 'ID', gettype($id));
            }
            $className = $this->resolveComponentClass($id, $definition);
            if ($className === null) {
                if ($definition !== null) {
                    $this->unresolvedComponents[$id] = true;
                }
                if (
                    is_array($definition)
                    && !isset($definition['class'])
                    && !isset($definition['__class'])
                    && !is_callable($definition, true)
                ) {
                    $this->componentsWithoutClass[$id] = true;
                }
                continue;
            }
            $this->components[$id] = $className;
            if (is_object($definition)) {
                $this->objectDefinedComponents[$id] = true;
            }
            if (is_array($definition)) {
                unset($definition['class'], $definition['__class']);
                $this->componentsDefinitions[$id] = $definition;
            }
        }
    }

    /** @param array<array-key, mixed> $services */
    private function processServices(array $services, string $label): void
    {
        foreach ($services as $id => $definition) {
            if (!is_string($id)) {
                $this->throwErrorWhenIsNotString($label, 'ID', gettype($id));
            }
            $definition = $this->unwrapServiceDefinition($definition);
            $className = $this->resolveServiceClass($id, $definition);
            unset($this->services[$id], $this->objectDefinedServices[$id], $this->unresolvedServices[$id]);
            if ($className !== null) {
                $this->services[$id] = $className;
                if (is_object($definition) && !$definition instanceof Instance) {
                    $this->objectDefinedServices[$id] = true;
                }
            } elseif ($definition !== null && $definition !== []) {
                $this->unresolvedServices[$id] = true;
            }
        }
    }

    /** @param array<array-key, mixed> $definition */
    private function resolveArrayClass(array $definition, string $key, string $fallbackKey): ?string
    {
        $class = $definition[$key] ?? $definition[$fallbackKey] ?? null;
        return is_string($class) ? $this->normalizeClassName($class) : null;
    }

    private function resolveComponentClass(string $id, mixed $definition): ?string
    {
        if ($definition === null) {
            return null;
        }
        if (is_array($definition)) {
            return is_callable($definition, true) ? null : $this->resolveArrayClass($definition, '__class', 'class');
        }
        return $this->resolveScalarOrObjectClass($id, $definition, $definition instanceof Closure);
    }

    private function resolveContainerAliases(): void
    {
        $services = [];
        foreach ($this->services as $id => $className) {
            $resolved = isset($this->objectDefinedServices[$id])
                ? $className
                : $this->followContainerAliases($className, [$id => true]);
            if ($resolved !== null) {
                $services[$id] = $resolved;
            } else {
                $this->unresolvedServices[$id] = true;
            }
        }
        foreach ($this->components as $id => $className) {
            if (isset($this->objectDefinedComponents[$id])) {
                continue;
            }
            $resolved = $this->followContainerAliases($className, []);
            if ($resolved === null) {
                unset($this->components[$id], $this->componentsDefinitions[$id]);
                $this->unresolvedComponents[$id] = true;
            } else {
                $this->components[$id] = $resolved;
            }
        }
        $this->services = $services;
    }

    private function resolveFactoryClass(ReflectionFunction|ReflectionMethod $factory): ?string
    {
        $returnType = $factory->getReturnType();
        if (!$returnType instanceof ReflectionNamedType || $returnType->isBuiltin() || $returnType->allowsNull()) {
            return null;
        }
        $class = $returnType->getName();
        return in_array(strtolower($class), self::RELATIVE_CLASS_TYPES, true) ? null : $class;
    }

    private function resolveIdAsClass(string $id): ?string
    {
        $class = ltrim($id, '\\');
        return preg_match(self::CLASS_NAME_PATTERN, $class) === 1
        && class_exists($class)
        && (new ReflectionClass($class))->isInstantiable()
            ? $class
            : null;
    }

    private function resolveScalarOrObjectClass(string $id, mixed $definition, bool $isFactory): ?string
    {
        if (is_string($definition)) {
            return $this->normalizeClassName($definition);
        }
        if (is_object($definition)) {
            if ($isFactory && $definition instanceof Closure) {
                return $this->resolveFactoryClass(new ReflectionFunction($definition));
            }
            if ($isFactory && method_exists($definition, '__invoke')) {
                return $this->resolveFactoryClass(new ReflectionMethod($definition, '__invoke'));
            }
            return $definition::class;
        }
        throw new RuntimeException(sprintf("Unsupported definition for '%s'.", $id));
    }

    private function resolveServiceClass(string $id, mixed $definition): ?string
    {
        if ($definition === null || $definition === []) {
            return $this->resolveIdAsClass($id);
        }
        if ($definition instanceof Instance) {
            return $this->normalizeClassName($definition->id);
        }
        if (!is_array($definition)) {
            return $this->resolveScalarOrObjectClass($id, $definition, true);
        }
        if (is_callable($definition, true)) {
            return null;
        }
        return (
            $this->resolveArrayClass($definition, 'class', '__class')
            ?? (str_contains($id, '\\') ? $this->resolveIdAsClass($id) : null)
        );
    }

    /**
     * @param array<array-key, mixed> $config
     * @return array<array-key, mixed>
     */
    private function section(array $config, string $key, string $configPath, string $path): array
    {
        $section = $config[$key] ?? [];
        if (!is_array($section)) {
            $this->throwErrorWhenConfigFileIsNotArray($configPath, $path);
        }
        return $section;
    }

    private function throwErrorWhenConfigFileIsNotArray(string $path, string $section): never
    {
        throw new RuntimeException(sprintf(
            "Configuration file '%s' must contain a valid '%s' 'array'.",
            $path,
            $section,
        ));
    }

    private function throwErrorWhenIsNotString(string $label, string $key, string $type): never
    {
        throw new RuntimeException(sprintf("'%s': '%s' must be a 'string', got '%s'.", $label, $key, $type));
    }

    private function unwrapServiceDefinition(mixed $definition): mixed
    {
        return is_array($definition)
        && array_is_list($definition)
        && count($definition) === 2
        && is_array($definition[1])
            ? $definition[0]
            : $definition;
    }
}
