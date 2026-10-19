<?php

declare(strict_types=1);

namespace yii2\extensions\mago\tests\Unit;

use ArrayObject;
use Closure;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SplObjectStorage;
use SplStack;
use stdClass;
use yii2\extensions\mago\ServiceMap;
use yii\base\InvalidArgumentException;
use yii\caching\ArrayCache;
use yii\caching\Cache;
use yii\caching\CacheInterface;
use yii\caching\FileCache;
use yii\di\Container;
use yii\di\Instance;
use yii\di\ServiceLocator;
use yii\web\Application;
use yii\web\View;

final class ServiceMapTest extends TestCase
{
    private const CONFIG = __DIR__ . '/../fixtures/config/inline.php';

    private function map(mixed $config): ServiceMap
    {
        $GLOBALS['yii2MagoTestConfig'] = $config;

        try {
            return new ServiceMap(self::CONFIG);
        } finally {
            unset($GLOBALS['yii2MagoTestConfig']);
        }
    }

    public function testEmptyConfiguration(): void
    {
        $map = new ServiceMap();
        self::assertSame(Application::class, $map->getApplicationType());
        self::assertSame([], $map->getParams());
        self::assertSame([], $map->getBehaviorsByClassName('absent'));
        self::assertNull($map->getServiceById('absent'));
        self::assertNull($map->getComponentClassById('absent'));
        self::assertFalse($map->isUnresolvedComponent('absent'));
        self::assertFalse($map->isUnresolvedService('absent'));
    }

    public function testApplicationMetadataAndParams(): void
    {
        $params = ['email' => 'admin@example.com', 'nested' => [0 => false, 'with space' => null]];
        $map = $this->map([
            'mago' => ['application_type' => \yii\console\Application::class],
            'phpstan' => ['application_type' => Application::class],
            'params' => $params,
            'behaviors' => [View::class => ['behavior', 1, false, 'other']],
        ]);
        self::assertSame(\yii\console\Application::class, $map->getApplicationType());
        self::assertSame($params, $map->getParams());
        self::assertSame(['behavior', 'other'], $map->getBehaviorsByClassName(View::class));
        self::assertSame('legacy', $this->map(['phpstan' => ['application_type' => 'legacy']])->getApplicationType());
    }

    public static function definitions(): iterable
    {
        yield 'class string' => [SplStack::class, SplStack::class, SplStack::class];
        yield 'leading slash' => ['\\' . SplStack::class, SplStack::class, SplStack::class];
        yield 'class array' => [['class' => SplStack::class], SplStack::class, SplStack::class];
        yield 'dunder class array' => [['__class' => SplStack::class], SplStack::class, SplStack::class];
        yield 'different class precedence' => [
            ['class' => SplStack::class, '__class' => ArrayObject::class],
            ArrayObject::class,
            SplStack::class,
        ];
        yield 'null class fallback' => [
            ['class' => null, '__class' => SplStack::class],
            SplStack::class,
            SplStack::class,
        ];
        yield 'untyped closure' => [static fn() => new SplStack(), null, null];
        yield 'typed closure' => [static fn(): SplStack => new SplStack(), SplStack::class, SplStack::class];
        yield 'nullable closure' => [static fn(): ?SplStack => null, null, null];
        yield 'union closure' => [static fn(): SplStack|ArrayObject => new SplStack(), null, null];
        yield 'builtin closure' => [static fn(): string => '', null, null];
        yield 'intersection closure' => [
            static function (): \Iterator&\Countable {
                throw new RuntimeException();
            },
            null,
            null,
        ];
        yield 'object' => [new SplStack(), SplStack::class, SplStack::class];
        yield 'instance reference' => [Instance::of(SplStack::class), Instance::class, SplStack::class];
        yield 'classless array' => [['option' => true], null, null];
        yield 'array callable' => [[self::class, 'definitions'], null, null];
        yield 'invalid preferred class' => [['class' => false, '__class' => SplStack::class], SplStack::class, null];
        yield 'invalid preferred dunder class' => [
            ['class' => SplStack::class, '__class' => false],
            null,
            SplStack::class,
        ];
        yield 'empty array' => [[], null, null];
        yield 'null' => [null, null, null];
        yield 'empty string' => ['', null, null];
        yield 'one item list' => [[['class' => SplStack::class]], null, null];
    }

    #[DataProvider('definitions')]
    public function testComponentAndContainerDefinitions(mixed $definition, ?string $component, ?string $service): void
    {
        $map = $this->map([
            'components' => ['test' => $definition],
            'container' => ['definitions' => ['test' => $definition]],
        ]);
        self::assertSame($component, $map->getComponentClassById('test'));
        self::assertSame($service, $map->getServiceById('test'));
        self::assertSame($component === null && $definition !== null, $map->isUnresolvedComponent('test'));
        self::assertSame(
            $service === null && $definition !== null && $definition !== [],
            $map->isUnresolvedService('test'),
        );
    }

    public function testInvokableObjectHasDifferentYiiSemantics(): void
    {
        $factory = new class {
            public function __invoke(): SplStack
            {
                return new SplStack();
            }
        };
        $map = $this->map([
            'components' => ['factory' => $factory],
            'container' => ['definitions' => ['factory' => $factory]],
        ]);
        self::assertSame($factory::class, $map->getComponentClassById('factory'));
        self::assertSame(SplStack::class, $map->getServiceById('factory'));
    }

    public function testMatchesActualYiiContainerAndServiceLocator(): void
    {
        $factory = new class {
            public function __invoke(): SplStack
            {
                return new SplStack();
            }
        };
        $definitions = [
            'stack' => SplStack::class,
            'array' => ['class' => ArrayObject::class],
            'object' => new stdClass(),
            'factory' => $factory,
        ];
        $map = $this->map(['components' => $definitions, 'container' => ['definitions' => $definitions]]);
        $container = new Container();
        $container->setDefinitions($definitions);
        $locator = new ServiceLocator();
        $locator->setComponents($definitions);
        foreach (array_keys($definitions) as $id) {
            self::assertSame($container->get($id)::class, $map->getServiceById($id));
            self::assertSame($locator->get($id)::class, $map->getComponentClassById($id));
        }
    }

    public function testClassPrecedenceMatchesActualYii(): void
    {
        $definition = ['class' => stdClass::class, '__class' => SplStack::class];
        $map = $this->map([
            'components' => ['test' => $definition],
            'container' => ['definitions' => ['test' => $definition]],
        ]);
        $container = new Container();
        $container->setDefinitions(['test' => $definition]);
        $locator = new ServiceLocator();
        $locator->setComponents(['test' => $definition]);
        self::assertSame($container->get('test')::class, $map->getServiceById('test'));
        self::assertSame($locator->get('test')::class, $map->getComponentClassById('test'));
    }

    public function testFactoriesAreReflectedWithoutExecutingThem(): void
    {
        $factory = static function (): SplStack {
            throw new RuntimeException('Factory must not run');
        };
        $relative = new class {
            public function __invoke(): self
            {
                throw new RuntimeException('Factory must not run');
            }
        };
        $map = $this->map([
            'components' => ['test' => $factory],
            'container' => ['definitions' => ['test' => $factory, 'relative' => $relative]],
        ]);
        self::assertSame(SplStack::class, $map->getServiceById('test'));
        self::assertSame(SplStack::class, $map->getComponentClassById('test'));
        self::assertNull($map->getServiceById('relative'));
        self::assertTrue($map->isUnresolvedService('relative'));
    }

    public function testAbsentAndNullSectionsAndClasslessCoreComponents(): void
    {
        $map = $this->map([
            'mago' => null,
            'phpstan' => null,
            'behaviors' => null,
            'container' => ['definitions' => null, 'singletons' => null],
            'components' => ['cache' => ['keyPrefix' => 'x'], 'gone' => null],
        ]);
        self::assertTrue($map->isComponentWithoutClass('cache'));
        self::assertTrue($map->isUnresolvedComponent('cache'));
        self::assertFalse($map->isUnresolvedComponent('gone'));
        self::assertFalse($map->isComponentWithoutClass('gone'));
        self::assertSame([], $map->getComponentDefinitionById('cache'));
        self::assertSame(Application::class, $map->getApplicationType());
    }

    public function testArbitraryIdsNeverReachAutoloaders(): void
    {
        $ids = [];
        $loader = static function (string $class) use (&$ids): void {
            $ids[] = $class;
        };
        spl_autoload_register($loader, true, true);
        try {
            $map = $this->map([
                'container' => [
                    'definitions' => ['id.with.dots' => [], 'id-with-dashes' => null, 'spaces in id' => []],
                ],
            ]);
            self::assertSame([], $ids);
            self::assertNull($map->getServiceById('id.with.dots'));
        } finally {
            spl_autoload_unregister($loader);
        }
    }

    public function testAliasesCyclesAndObjects(): void
    {
        $map = $this->map([
            'components' => [
                'alias' => 'start',
                'cycle' => 'a',
                'unresolved' => 'unknown',
                'object' => new ArrayCache(),
                'factory' => static fn(): CacheInterface => new ArrayCache(),
                'classless' => ['enable' => true],
                'withDefinition' => ['class' => 'start', 'x' => true],
            ],
            'container' => [
                'definitions' => [
                    'start' => ['class' => 'next'],
                    'next' => Instance::of(CacheInterface::class),
                    CacheInterface::class => FileCache::class,
                    ArrayCache::class => FileCache::class,
                    'a' => 'b',
                    'b' => 'a',
                    'unknown' => static fn() => null,
                    'aliasUnknown' => 'unknown',
                    'object' => new ArrayCache(),
                    'objectAlias' => 'object',
                    'factory' => static fn(): CacheInterface => new ArrayCache(),
                    'factoryAlias' => 'factory',
                ],
            ],
        ]);
        foreach (['start', 'next', CacheInterface::class] as $id) {
            self::assertSame(FileCache::class, $map->getServiceById($id));
        }
        self::assertSame(FileCache::class, $map->getComponentClassById('alias'));
        self::assertSame(['x' => true], $map->getComponentDefinitionById('withDefinition'));
        self::assertSame(ArrayCache::class, $map->getComponentClassById('object'));
        self::assertSame(ArrayCache::class, $map->getServiceById('objectAlias'));
        self::assertSame(CacheInterface::class, $map->getServiceById('factoryAlias'));
        self::assertSame(CacheInterface::class, $map->getComponentClassById('factory'));
        self::assertTrue($map->isComponentWithoutClass('classless'));
        foreach (['a', 'b', 'unknown', 'aliasUnknown'] as $id) {
            self::assertNull($map->getServiceById($id));
            self::assertTrue($map->isUnresolvedService($id));
        }
        self::assertTrue($map->isUnresolvedComponent('cycle'));
        self::assertTrue($map->isUnresolvedComponent('unresolved'));
        self::assertSame([], $map->getComponentDefinitionById('cycle'));
    }

    public function testContainerOrderPriorityAndWrappedDefinitions(): void
    {
        foreach ([false, true] as $reverse) {
            $sections = ['definitions' => ['test' => new ArrayObject()], 'singletons' => ['test' => SplStack::class]];
            $map = $this->map(['container' => $reverse ? array_reverse($sections, true) : $sections]);
            self::assertSame($reverse ? ArrayObject::class : SplStack::class, $map->getServiceById('test'));
        }
        $map = $this->map([
            'container' => [
                'definitions' => [
                    'unresolved' => SplStack::class,
                    'empty' => SplStack::class,
                    'resolved' => static fn() => null,
                ],
                'singletons' => [
                    'unresolved' => static fn() => null,
                    'empty' => [],
                    'resolved' => SplStack::class,
                    'wrapped' => [['class' => SplStack::class], []],
                    'wrappedString' => [SplStack::class, [1]],
                    'nested' => [['class' => SplStack::class]],
                ],
            ],
        ]);
        self::assertTrue($map->isUnresolvedService('unresolved'));
        self::assertFalse($map->isUnresolvedService('empty'));
        self::assertNull($map->getServiceById('empty'));
        foreach (['resolved', 'wrapped', 'wrappedString'] as $id) {
            self::assertSame(SplStack::class, $map->getServiceById($id));
        }
        self::assertTrue($map->isUnresolvedService('nested'));
    }

    public function testClassIdsAndEmptyDefinitions(): void
    {
        $map = $this->map([
            'container' => [
                'definitions' => [
                    SplObjectStorage::class => [],
                    View::class => null,
                    stdClass::class => null,
                    Cache::class => ['property' => true],
                    CacheInterface::class => ['property' => true],
                    'missing\\ClassName' => ['property' => true],
                    'arbitrary-id' => null,
                ],
            ],
        ]);
        foreach ([SplObjectStorage::class, View::class, stdClass::class] as $id) {
            self::assertSame($id, $map->getServiceById($id));
        }
        foreach ([Cache::class, CacheInterface::class, 'missing\\ClassName'] as $id) {
            self::assertNull($map->getServiceById($id));
            self::assertTrue($map->isUnresolvedService($id));
        }
        self::assertFalse($map->isUnresolvedService('arbitrary-id'));
    }

    public static function invalidConfigs(): iterable
    {
        yield 'non-array config' => [false, 'must return an array'];
        foreach (['mago', 'phpstan', 'behaviors', 'components', 'params', 'container'] as $section) {
            yield $section => [[$section => false], "valid '$section' 'array'"];
        }
        yield 'null params' => [['params' => null], "valid 'params' 'array'"];
        foreach (['definitions', 'singletons'] as $section) {
            yield $section => [['container' => [$section => false]], "valid 'container.$section' 'array'"];
        }
        yield 'application type' => [
            ['mago' => ['application_type' => false]],
            "'mago.application_type' must be a 'string'",
        ];
        yield 'behavior id' => [['behaviors' => [0 => []]], "'Behavior class': 'ID' must be a 'string'"];
        yield 'behavior definition' => [
            ['behaviors' => ['bad' => false]],
            "Behavior definition for 'bad' must be an array",
        ];
        yield 'component id' => [['components' => [0 => []]], "'Component': 'ID' must be a 'string'"];
        yield 'component definition' => [['components' => ['bad' => false]], "Unsupported definition for 'bad'"];
        yield 'definition id' => [
            ['container' => ['definitions' => [0 => []]]],
            "'Definition': 'ID' must be a 'string'",
        ];
        yield 'singleton id' => [['container' => ['singletons' => [0 => []]]], "'Singleton': 'ID' must be a 'string'"];
        yield 'service definition' => [
            ['container' => ['definitions' => ['bad' => 42]]],
            "Unsupported definition for 'bad'",
        ];
    }

    #[DataProvider('invalidConfigs')]
    public function testInvalidConfiguration(mixed $config, string $message): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($message);
        $this->map($config);
    }

    public function testInvalidPath(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must be a readable PHP file');
        new ServiceMap('/missing/config.php');
    }
}
