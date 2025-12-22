# Change log

## 6.1.0

- [#791](https://github.com/PHP-DI/PHP-DI/issues/791) Support PHP 8.1, remove support for PHP 7.2

## 6.0.2

- Fix potential regression introduced when fixing [#582](https://github.com/PHP-DI/PHP-DI/issues/582)

## 6.0.1

- Fix [#526](https://github.com/PHP-DI/PHP-DI/issues/526): Support optional parameters in factories
- [#585](https://github.com/PHP-DI/PHP-DI/issues/585) Add support for PHP-Parser 4.0
- [#582](https://github.com/PHP-DI/PHP-DI/issues/582) Register `ContainerInterface` to point to the wrapper container if it was defined

## 6.0

This is the complete change log. You can also read the [migration guide](doc/migration/6.0.md) for upgrading or the [blog article](news/22-php-di-6-0-released.md) to see what's new.

Improvements:

- [#494](https://github.com/PHP-DI/PHP-DI/pull/494) The container can now be compiled for optimum performances in production
- [#294](https://github.com/PHP-DI/PHP-DI/issues/294), [#349](https://github.com/PHP-DI/PHP-DI/issues/349), [#449](https://github.com/PHP-DI/PHP-DI/pull/449): `DI\object()` has been replaced by more specific and less ambiguous helpers:
    - `DI\create()` creates an object, overrides autowiring and previous definitions
    - `DI\autowire()` autowires an object and allows to override specific constructor and method parameters
- The container can now be built without parameters: `new Container()`
- Definitions can be nested:
    - [#490](https://github.com/PHP-DI/PHP-DI/issues/490) Definitions can be nested in arrays (by [@yuloh](https://github.com/yuloh))
    - [#501](https://github.com/PHP-DI/PHP-DI/issues/501) & [#540](https://github.com/PHP-DI/PHP-DI/issues/540) Autowire definitions can be nested in other definitions
    - [#487](https://github.com/PHP-DI/PHP-DI/issues/487) & [#540](https://github.com/PHP-DI/PHP-DI/issues/540) Closures are now handled as factories when they are nested in other definitions
- [#487](https://github.com/PHP-DI/PHP-DI/issues/487) Closures in the config are now always interpreted as factories, even when nested inside other definitions
- [#242](https://github.com/PHP-DI/PHP-DI/issues/242) Error in case a definition is not indexed by a string
- [#505](https://github.com/PHP-DI/PHP-DI/pull/505) Debug container entries
- [#564](https://github.com/PHP-DI/PHP-DI/pull/564) Caching was made almost entirely obsolete by the container compilation, however there is still a caching system entirely rebuilt over APCu for covering the last cases that compilation could not address (see [php-di.org/doc/performances.html](https://php-di.org/doc/performances.html))

Fixes:

- [#499](https://github.com/PHP-DI/PHP-DI/issues/499) & [#488](https://github.com/PHP-DI/PHP-DI/issues/488) Standardize resolution of nested definitions everywhere.
    In PHP-DI 5, definitions could be nested in some places (e.g. use a get() in an object definition, etc.). However it did not behave everywhere the same, for example it didn't work for sub-definitions in arrays.
    Now in PHP-DI 6 all nested definitions will all be recognized and resolved correctly everywhere. Since #494 (compiled container) performance will not be affected so we can implement a more robust behavior.
- [#343](https://github.com/PHP-DI/PHP-DI/issues/343) Autowiring and Annotations do not work for `object()` inside arrays: it now works with the new `create()` and `autowire()` helpers

BC breaks:

- PHP 7 or greater is required and HHVM is no longer supported
- `DI\object()` has been removed, use `DI\create()` or `DI\autowire()` instead
- [#409](https://github.com/PHP-DI/PHP-DI/issues/409): Scopes are removed, read more in the [scopes](doc/scopes.md) documentation.
- Caching was replaced by compiling the container: `ContainerBuilder::setDefinitionCache()` was removed, use `ContainerBuilder::enableCompilation()` instead.
- [#463](https://github.com/PHP-DI/PHP-DI/issues/463) & [#485](https://github.com/PHP-DI/PHP-DI/issues/485): Container-interop support was removed, PSR-11 is used instead (by [@juliangut](https://github.com/juliangut))
- The deprecated `DI\link()` helper was removed, used `DI\get()` instead
- [#484](https://github.com/PHP-DI/PHP-DI/pull/484) The deprecated `\DI\Debug` class has been removed. Definitions can be cast to string directly
- The exception `DI\Definition\Exception\DefinitionException` was renamed to `DI\Definition\Exception\InvalidDefinition`
- The exception `DI\Definition\Exception\AnnotationException` was renamed to `DI\Definition\Exception\InvalidAnnotation`
- [#516](https://github.com/PHP-DI/PHP-DI/issues/516) `DI\InvokerInterface` was removed in favor of `Invoker\InvokerInterface`.

Be also aware that internal classes or interfaces may have changed.

## 5.4.6

- Fix [#554](https://github.com/PHP-DI/PHP-DI/issues/554): `Container::make()` fails when combined with `decorate()`.

## 5.4.5

Fixup of 5.4.4.

- [#531](https://github.com/PHP-DI/PHP-DI/issues/531): performance improvement.

## 5.4.4

This release was broken because it was tagged against the wrong branch.

- [#531](https://github.com/PHP-DI/PHP-DI/issues/531): performance improvement.

## 5.4.3

- [#467](https://github.com/PHP-DI/PHP-DI/issues/467): register the container against the PSR ContainerInterface

## 5.4.2

- Minor patch to add the `provide: psr/container-implementation` to `composer.json`.

## 5.4.1

- [PSR-11](http://www.php-fig.org/psr/) compliance

Note that PHP-DI was already compliant with PSR-11 because it was implementing container-interop, and container-interop 1.2 extends PSR-11. This new version just makes it more explicit and will allow to drop container-interop support in the next major versions.

## 5.4

Read the [news entry](news/20-php-di-5-4-released.md).

New features:

- [#362](https://github.com/PHP-DI/PHP-DI/issues/362) implemented in [#428](https://github.com/PHP-DI/PHP-DI/pull/428), [#430](https://github.com/PHP-DI/PHP-DI/pull/430), [#431](https://github.com/PHP-DI/PHP-DI/pull/431) and [#432](https://github.com/PHP-DI/PHP-DI/pull/432): factory parameters can now be configured, for example:
    ```php
    return [
        'Database' => DI\factory(function ($host) {...})
            ->parameter('host', DI\get('db.host')),
    ];
    ```
    Read the [factories documentation](https://php-di.org/doc/php-definitions.html#factories) to learn more. Feature implemented by [@predakanga](https://github.com/predakanga).

Improvements:

- [#429](https://github.com/PHP-DI/PHP-DI/pull/429): performance improvements in definition resolution (by [@mnapoli](https://github.com/mnapoli))
- [#421](https://github.com/PHP-DI/PHP-DI/issues/421): once a `ContainerBuilder` has built a container, it is locked to prevent confusion when adding new definitions to it (by [@mnapoli](https://github.com/mnapoli))
- [#423](https://github.com/PHP-DI/PHP-DI/pull/423): improved exception messages (by [@mnapoli](https://github.com/mnapoli))

## 5.3

Read the [news entry](news/19-php-di-5-3-released.md).

- release of the [2.0 version](https://github.com/PHP-DI/Symfony-Bridge/releases/tag/2.0.0) of the Symfony bridge (by [@mnapoli](https://github.com/mnapoli))
- PHP 5.5 or above is now required
- a lot of documentation improvements by 9 different contributors
- [#389](https://github.com/PHP-DI/PHP-DI/pull/389): exception message improvement by [@mopahle](https://github.com/mopahle)
- [#359](https://github.com/PHP-DI/PHP-DI/issues/359), [#411](https://github.com/PHP-DI/PHP-DI/issues/411), [#414](https://github.com/PHP-DI/PHP-DI/pull/414), [#412](https://github.com/PHP-DI/PHP-DI/pull/412): compatibility with ProxyManager 1.* and 2.* (by [@holtkamp](https://github.com/holtkamp) and [@mnapoli](https://github.com/mnapoli))
- [#416](https://github.com/PHP-DI/PHP-DI/pull/416): dumping definitions was refactored into a more lightweight and simple solution; definition "dumpers" have been removed (internal classes), definitions can now be cast to string directly (by [@mnapoli](https://github.com/mnapoli))

## 5.2

Read the [news entry](news/17-php-di-5-2-released.md).

Improvements:

- [#347](https://github.com/PHP-DI/PHP-DI/pull/347) (includes [#333](https://github.com/PHP-DI/PHP-DI/pull/333) and [#345](https://github.com/PHP-DI/PHP-DI/pull/345)): by [@jdreesen](https://github.com/jdreesen), [@quimcalpe](https://github.com/quimcalpe) and [@mnapoli](https://github.com/mnapoli)
    - Allow injection of any container object as factory parameter via type hinting
    - Allow injection of a `DI\Factory\RequestedEntry` object to get the requested entry name
- [#272](https://github.com/PHP-DI/PHP-DI/issues/272): Support `"Class::method""` syntax for callables (by [@jdreesen](https://github.com/jdreesen))
- [#332](https://github.com/PHP-DI/PHP-DI/issues/332): IDE support (plugin and documentation) (by [@pulyaevskiy](https://github.com/pulyaevskiy), [@avant1](https://github.com/avant1) and [@mnapoli](https://github.com/mnapoli))
- [#326](https://github.com/PHP-DI/PHP-DI/pull/326): Exception messages are simpler and more consistent (by [@mnapoli](https://github.com/mnapoli))
- [#325](https://github.com/PHP-DI/PHP-DI/pull/325): Add a "Edit this page" button in the website to encourage users to improve the documentation (by [@jdreesen](https://github.com/jdreesen))

Bugfixes:

- [#321](https://github.com/PHP-DI/PHP-DI/pull/321): Allow factory definitions to reference arbitrary container entries as callables (by [@jdreesen](https://github.com/jdreesen))
- [#335](https://github.com/PHP-DI/PHP-DI/issues/335): Class imports in traits are now considered when parsing annotations (by [@thebigb](https://github.com/thebigb))

## 5.1

Read the [news entry](news/16-php-di-5-1-released.md).

Improvements:

- [Zend Framework 2 integration](https://github.com/PHP-DI/ZF2-Bridge) (by @Rastusik)
- [#308](https://github.com/PHP-DI/PHP-DI/pull/308): Instantiate factories using the container (`DI\factory(['FooFactory', 'create'])`)
- Many performances improvements - some benchmarks show up to 35% performance improvements, real results may vary of course
- Many documentation improvements (@jdreesen, @mindplay-dk, @mnapoli, @holtkamp, @Rastusik)
- [#296](https://github.com/PHP-DI/PHP-DI/issues/296): Provide a faster `ArrayCache` implementation, mostly useful in micro-benchmarks

Bugfixes:

- [#257](https://github.com/PHP-DI/PHP-DI/issues/257) & [#274](https://github.com/PHP-DI/PHP-DI/issues/274): Private properties of parent classes are not injected when using annotations
- [#300](https://github.com/PHP-DI/PHP-DI/pull/300): Exception if object definition extends an incompatible definition
- [#306](https://github.com/PHP-DI/PHP-DI/issues/306): Errors when using parameters passed by reference (fixed by @bradynpoulsen)
- [#318](https://github.com/PHP-DI/PHP-DI/issues/318): `Container::call()` ignores parameter's default value

Internal changes:

- [#276](https://github.com/PHP-DI/PHP-DI/pull/276): Tests now pass on Windows (@bgaillard)

## 5.0

This is the complete change log. You can also read the [migration guide](doc/migration/5.0.md) for upgrading, or [the news article](news/15-php-di-5-0-released.md) for a nicer introduction to this new version.

Improvements:

- Moved to an organization on GitHub: [github.com/PHP-DI/PHP-DI](https://github.com/PHP-DI/PHP-DI)
- The package has been renamed to: from `mnapoli/php-di` to [`php-di/php-di`](https://packagist.org/packages/php-di/php-di)
- New [Silex integration](doc/frameworks/silex.md)
- Lighter package: from 10 to 3 Composer dependencies!
- [#235](https://github.com/PHP-DI/PHP-DI/issues/235): `DI\link()` is now deprecated in favor of `DI\get()`. There is no BC break as `DI\link()` still works.
- [#207](https://github.com/PHP-DI/PHP-DI/issues/207): Support for `DI\link()` in arrays
- [#203](https://github.com/PHP-DI/PHP-DI/issues/203): New `DI\string()` helper ([documentation](doc/php-definitions.md))
- [#208](https://github.com/PHP-DI/PHP-DI/issues/208): Support for nested definitions
- [#226](https://github.com/PHP-DI/PHP-DI/pull/226): `DI\factory()` can now be omitted with closures:

    ```php
    // before
    'My\Class' => DI\factory(function () { ... })
    // now (optional shortcut)
    'My\Class' => function () { ... }
    ```
- [#193](https://github.com/PHP-DI/PHP-DI/issues/193): `DI\object()->method()` now supports calling the same method twice (or more).
- [#248](https://github.com/PHP-DI/PHP-DI/issues/248): New `DI\decorate()` helper to decorate a previously defined entry ([documentation](doc/definition-overriding.md))
- [#215](https://github.com/PHP-DI/PHP-DI/pull/215): New `DI\add()` helper to add entries to an existing array ([documentation](doc/definition-overriding.md))
- [#218](https://github.com/PHP-DI/PHP-DI/issues/218): `ContainerBuilder::addDefinitions()` can now take an array of definitions
- [#211](https://github.com/PHP-DI/PHP-DI/pull/211): `ContainerBuilder::addDefinitions()` is now fluent (return `$this`)
- [#250](https://github.com/PHP-DI/PHP-DI/issues/250): `Container::call()` now also accepts parameters not indexed by name as well as embedded definitions ([documentation](doc/container.md))
- Various performance improvements, e.g. lower the number of files loaded, simpler architecture, …

BC breaks:

- PHP-DI now requires a version of PHP >= 5.4.0
- The package is lighter by default:
    - [#251](https://github.com/PHP-DI/PHP-DI/issues/251): Annotations are disabled by default, if you use annotations enable them with `$containerBuilder->useAnnotations(true)`. Additionally the `doctrine/annotations` package isn't required by default anymore, so you also need to run `composer require doctrine/annotations`.
    - `doctrine/cache` is not installed by default anymore, you need to require it in `composer.json` (`~1.0`) if you want to configure a cache for PHP-DI
    - [#198](https://github.com/PHP-DI/PHP-DI/issues/198): `ocramius/proxy-manager` is not installed by default anymore, you need to require it in `composer.json` (`~1.0`) if you want to use **lazy injection**
- Closures are now converted into factory definitions automatically. If you ever defined a closure as a value (e.g. to have the closure injected in a class), you need to wrap the closure with the new `DI\value()` helper.
- [#223](https://github.com/PHP-DI/PHP-DI/issues/223): `DI\ContainerInterface` was deprecated since v4.1 and has been removed

Internal changes in case you were replacing/extending some parts:

- the definition sources architecture has been refactored, if you defined custom definition sources you will need to update your code (it should be much easier now)
- [#252](https://github.com/PHP-DI/PHP-DI/pull/252): `DI\Scope` internal implementation has changed. You are encouraged to use the constants (`DI\Scope::SINGLETON` and `DI\Scope::PROTOTYPE`) instead of the static methods, but backward compatibility is kept (static methods still work).
- [#241](https://github.com/PHP-DI/PHP-DI/issues/241): `Container::call()` now uses the *Invoker* external library

## 4.4

Read the [news entry](news/13-php-di-4-4-released.md).

- [#185](https://github.com/PHP-DI/PHP-DI/issues/185) Support for invokable objects in `Container::call()`
- [#192](https://github.com/PHP-DI/PHP-DI/pull/192) Support for invokable classes in `Container::call()` (will instantiate the class)
- [#184](https://github.com/PHP-DI/PHP-DI/pull/184) Option to ignore phpdoc errors

## 4.3

Read the [news entry](news/11-php-di-4-3-released.md).

- [#176](https://github.com/PHP-DI/PHP-DI/pull/176) New definition type for reading environment variables: `DI\env()`
- [#181](https://github.com/PHP-DI/PHP-DI/pull/181) `DI\FactoryInterface` and `DI\InvokerInterface` are now auto-registered inside the container so that you can inject them without any configuration needed
- [#173](https://github.com/PHP-DI/PHP-DI/pull/173) `$container->call(['MyClass', 'method]);` will get `MyClass` from the container if `method()` is not a static method

## 4.2.2

- Fixed [#180](https://github.com/PHP-DI/PHP-DI/pull/180): `Container::call()` with object methods (`[$object, 'method']`) is now supported

## 4.2.1

- Support for PHP 5.3.3, which was previously incomplete because of a bug in the reflection (there is now a workaround for this bug)

But if you can, seriously avoid this (really old) PHP version and upgrade.

## 4.2

Read the [news entry](news/10-php-di-4-2-released.md).

**Minor BC-break**: Optional parameters (that were not configured) were injected, they are now ignored, which is what naturally makes sense since they are optional.
Example:

```php
    public function __construct(Bar $bar = null)
    {
        $this->bar = $bar ?: $this->createDefaultBar();
    }
```

Before 4.2, PHP-DI would try to inject a `Bar` instance. From 4.2 and onwards, it will inject `null`.

Of course, you can still explicitly define an injection for the optional parameters and that will work.

All changes:

* [#162](https://github.com/PHP-DI/PHP-DI/pull/162) Added `Container::call()` to call functions with dependency injection
* [#156](https://github.com/PHP-DI/PHP-DI/issues/156) Wildcards (`*`) in definitions
* [#164](https://github.com/PHP-DI/PHP-DI/issues/164) Prototype scope is now available for `factory()` definitions too
* FIXED [#168](https://github.com/PHP-DI/PHP-DI/pull/168) `Container::has()` now returns false for interfaces and abstract classes that are not mapped in the definitions
* FIXED [#171](https://github.com/PHP-DI/PHP-DI/issues/171) Optional parameters are now ignored (not injected) if not set in the definitions (see the BC-break warning above)

## 4.1

Read the [news entry](news/09-php-di-4-1-released.md).

BC-breaks: None.

* [#138](https://github.com/PHP-DI/PHP-DI/issues/138) [Container-interop](https://github.com/container-interop/container-interop) compliance
* [#143](https://github.com/PHP-DI/PHP-DI/issues/143) Much more explicit exception messages
* [#157](https://github.com/PHP-DI/PHP-DI/issues/157) HHVM support
* [#158](https://github.com/PHP-DI/PHP-DI/issues/158) Improved the documentation for [Symfony 2 integration](https://php-di.org/doc/frameworks/symfony2.html)

## 4.0

Major changes:

* The configuration format has changed ([read more here to understand why](news/06-php-di-4-0-new-definitions.md))

Read the migration guide if you are using 3.x: [Migration guide from 3.x to 4.0](doc/migration/4.0.md).

BC-breaks:

* YAML, XML and JSON definitions have been removed, and the PHP definition format has changed (see above)
* `ContainerSingleton` has been removed
* You cannot configure an injection as lazy anymore, you can only configure a container entry as lazy
* The Container constructor now takes mandatory parameters. Use the ContainerBuilder to create a Container.
* Removed `ContainerBuilder::setDefinitionsValidation()` (no definition validation anymore)
* `ContainerBuilder::useReflection()` is now named: `ContainerBuilder::useAutowiring()`
* `ContainerBuilder::addDefinitionsFromFile()` is now named: `ContainerBuilder::addDefinitions()`
* The `$proxy` parameter in `Container::get($name, $proxy = true)` hase been removed. To get a proxy, you now need to define an entry as "lazy".

Other changes:

* Added `ContainerInterface` and `FactoryInterface`, both implemented by the container.
* [#115](https://github.com/PHP-DI/PHP-DI/issues/115) Added `Container::has()`
* [#142](https://github.com/PHP-DI/PHP-DI/issues/142) Added `Container::make()` to resolve an entry
* [#127](https://github.com/PHP-DI/PHP-DI/issues/127) Added support for cases where PHP-DI is wrapped by another container (like Acclimate): PHP-DI can now use the wrapping container to perform injections
* [#128](https://github.com/PHP-DI/PHP-DI/issues/128) Configure entry aliases
* [#110](https://github.com/PHP-DI/PHP-DI/issues/110) XML definitions are not supported anymore
* [#122](https://github.com/PHP-DI/PHP-DI/issues/122) JSON definitions are not supported anymore
* `ContainerSingleton` has finally been removed
* Added `ContainerBuilder::buildDevContainer()` to get started with a default container very easily.
* [#99](https://github.com/PHP-DI/PHP-DI/issues/99) Fixed "`@param` with PHP internal type throws exception"

## 3.5.1

* FIXED [#126](https://github.com/PHP-DI/PHP-DI/issues/126): `Container::set` without effect if a value has already been set and retrieved

## 3.5

Read the [news entry](news/05-php-di-3-5.md).

* Importing `@Inject` and `@Injectable` annotations is now optional! It means that you don't have to write `use DI\Annotation\Inject` anymore
* FIXED [#124](https://github.com/PHP-DI/PHP-DI/issues/124): `@Injects` annotation conflicts with other annotations

## 3.4

Read the [news entry](news/04-php-di-3-4.md).

* [#106](https://github.com/PHP-DI/PHP-DI/pull/106) You can now define arrays of values (in YAML, PHP, …) thanks to [@unkind](https://github.com/unkind)
* [#98](https://github.com/PHP-DI/PHP-DI/issues/98) `ContainerBuilder` is now fluent thanks to [@drdamour](https://github.com/drdamour)
* [#101](https://github.com/PHP-DI/PHP-DI/pull/101) Optional parameters are now supported: if you don't define a value to inject, their default value will be used
* XML definitions have been deprecated, there weren't even documented and were not maintained. They will be removed in 4.0.
* FIXED [#100](https://github.com/PHP-DI/PHP-DI/issues/100): bug for lazy injection in constructors

## 3.3

Read the [news entry](news/03-php-di-3-3.md).

* Inject dependencies on an existing instance with `Container::injectOn` (work from [Jeff Flitton](https://github.com/jflitton): [#89](https://github.com/PHP-DI/PHP-DI/pull/89)).
* [#86](https://github.com/PHP-DI/PHP-DI/issues/86): Optimized definition lookup (faster)
* FIXED [#87](https://github.com/PHP-DI/PHP-DI/issues/87): Rare bug in the `PhpDocParser`, fixed by [drdamour](https://github.com/drdamour)

## 3.2

Read the [news entry](news/02-php-di-3-2.md).

Small BC-break: PHP-DI 3.0 and 3.1 injected properties before calling the constructor. This was confusing and [not supported for internal classes](https://github.com/PHP-DI/PHP-DI/issues/74).
From 3.2 and on, properties are injected after calling the constructor.

* **[Lazy injection](doc/lazy-injection.md)**: it is now possible to use lazy injection on properties and methods (setters and constructors).
* Lazy dependencies are now proxies that extend the class they proxy, so type-hinting works.
* Addition of the **`ContainerBuilder`** object, that helps to [create and configure a `Container`](doc/container-configuration.md).
* Some methods for configuring the Container have gone **deprecated** in favor of the `ContainerBuilder`. Fear not, these deprecated methods will remain until next major version (4.0).
    * `Container::useReflection`, use ContainerBuilder::useReflection instead
    * `Container::useAnnotations`, use ContainerBuilder::useAnnotations instead
    * `Container::setDefinitionCache`, use ContainerBuilder::setDefinitionCache instead
    * `Container::setDefinitionsValidation`, use ContainerBuilder::setDefinitionsValidation instead
* The container is now auto-registered (as 'DI\Container'). You can now inject the container without registering it.

## 3.1.1

* Value definitions (`$container->set('foo', 80)`) are not cached anymore
* FIXED [#82](https://github.com/PHP-DI/PHP-DI/issues/82): Serialization error when using a cache

## 3.1

Read the [news entry](news/01-php-di-3-1.md).

* Zend Framework 1 integration through the [PHP-DI-ZF1 project](https://github.com/PHP-DI/PHP-DI-ZF1)
* Fixed the order of priorities when you mix different definition sources (reflection, annotations, files, …). See [Definition overriding](doc/definition-overriding.md)
* Now possible to define null values with  `$container->set('foo', null)` (see [#79](https://github.com/PHP-DI/PHP-DI/issues/79)).
* Deprecated usage of `ContainerSingleton`, will be removed in next major version (4.0)

## 3.0.6

* FIXED [#76](https://github.com/PHP-DI/PHP-DI/issues/76): Definition conflict when setting a closure for a class name

## 3.0.5

* FIXED [#70](https://github.com/PHP-DI/PHP-DI/issues/70): Definition conflict when setting a value for a class name

## 3.0.4

* FIXED [#69](https://github.com/PHP-DI/PHP-DI/issues/69): YamlDefinitionFileLoader crashes if YAML file is empty

## 3.0.3

* Fixed over-restrictive dependencies in composer.json

## 3.0.2

* [#64](https://github.com/PHP-DI/PHP-DI/issues/64): Non PHP-DI exceptions are not captured-rethrown anymore when injecting dependencies (cleaner stack trace)

## 3.0.1

* [#62](https://github.com/PHP-DI/PHP-DI/issues/62): When using aliases, definitions are now merged

## 3.0

Major compatibility breaks with 2.x.

* The container is no longer a Singleton (but `ContainerSingleton::getInstance()` is available for fools who like it)
* Setter injection
* Constructor injection
* Scopes: singleton (share the same instance of the class) or prototype (create a new instance each time it is fetched). Defined at class level.
* Configuration is reworked from scratch. Now every configuration backend can do 100% of the job.
* Provided configuration backends:
    * Reflection
    * Annotations: @Inject, @Injectable
    * PHP code (`Container::set()`)
    * PHP array
    * YAML file
* As a consequence, annotations are not mandatory anymore, all functionalities can be used with or without annotations.
* Renamed `DI\Annotations\` to `DI\Annotation\`
* `Container` no longer implements ArrayAccess, use only `$container->get($key)` now
* ZF1 integration broken and removed (work in progress for next releases)
* Code now follows PSR1 and PSR2 coding styles
* FIXED: [#58](https://github.com/PHP-DI/PHP-DI/issues/58) Getting a proxy of an alias didn't work

## 2.1

* `use` statements to import classes from other namespaces are now taken into account with the `@var` annotation
* Updated and lightened the dependencies : `doctrine/common` has been replaced with more specific `doctrine/annotations` and `doctrine/cache`

## 2.0

Major compatibility breaks with 1.x.

* `Container::resolveDependencies()` has been renamed to `Container::injectAll()`
* Dependencies are now injected **before** the constructor is called, and thus are available in the constructor
* Merged `@Value` annotation with `@Inject`: no difference between value and bean injection anymore
* Container implements ArrayAccess for get() and set() (`$container['db.host'] = 'localhost';`)
* Ini configuration files removed: configuration is done in PHP
* Allow to define beans within closures for lazy-loading
* Switched to MIT License

Warning:

* If you use PHP 5.3 and __wakeup() methods, they will be called when PHP-DI creates new instances of those classes.

## 1.1

* Caching of annotations based on Doctrine caches

## 1.0

* DependencyManager renamed to Container
* Refactored basic Container usage with `get` and `set`
* Allow named injection `@Inject(name="")`
* Zend Framework integration
