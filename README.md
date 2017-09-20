[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/thecodingmachine/service-provider-registry/badges/quality-score.png?b=3.0)](https://scrutinizer-ci.com/g/thecodingmachine/service-provider-registry/?branch=3.0)
[![Build Status](https://travis-ci.org/thecodingmachine/service-provider-registry.svg?branch=3.0)](https://travis-ci.org/thecodingmachine/service-provider-registry)
[![Coverage Status](https://coveralls.io/repos/thecodingmachine/service-provider-registry/badge.svg?branch=3.0&service=github)](https://coveralls.io/github/thecodingmachine/service-provider-registry?branch=3.0)


What is it?
===========

This project contains a registry that stores service providers. The registry implements `\ArrayAccess` and behaves like an array.
However, service providers in this object can be instantiated only when the key in the array is fetched, so you don't have to create the instance right away. This is useful for performance considerations, especially for compiled or cached containers.

This class is meant to be used by compiled/cached containers that want to implement [container-interop service providers](http://github.com/container-interop/service-provider). It is not meant for the mere mortals.

How does it work?
=================

Easy, you create a new `Registry` object, and then, you push objects in it.

```php
$registry = new Registry();

$key = $registry->push(MyServiceProvider::class);

// This will trigger the creation of the MyServiceProvider object and return it.
$serviceProvider = $registry[$key];
```

You can also pass parameters to the constructor of the object:

```php
$registry = new Registry();

$key = $registry->push(MyServiceProvider::class, "param1", "param2");
```

And because we are kind, you can also push into the lazy array an already instantiated object:

```php
$registry = new Registry();

// This is possible, even if we loose the interest of the Registry.
$key = $registry->push(new MyServiceProvider());
```


Finally, if you are performance oriented (and I'm sure you are, otherwise you wouldn't be looking at this package), you can create the whole registry in one call:

```php
$registry = new Registry([
    MyServiceProvider::class, // Is you simply want to create an instance without passing parameters
    [ MyServiceProvider2::class, [ "param1", "param2 ] ],  // Is you simply want to create an instance and pass parameters to the constructor
    new MyServiceProvider4('foo') // If you directly want to push the constructed instance.
]);
```

Iterating the registry
======================

The registry implements the `\Traversable` interface, so iterating it is as simple as a `foreach`:

```php
foreach ($registry as $serviceProvider) {
    // Do stuff for each service provider.
    // Service providers will be instantiated on the fly if needed.
}
```

Discovery with thecodingmachine/discovery
=========================================

The registry supports a discovery mechanism based on [thecodingmachine/discovery](https://thecodingmachine.github.io/discovery/) (to automatically find and attach service providers to your application).

As a second parameter, the `Registry` accepts the `Discovery` object from [thecodingmachine/discovery](https://github.com/thecodingmachine/discovery). Pass this object and thecodingmachine/discovery will be used to fetch service providers from your packages.

```php
$registry = new Registry([], TheCodingMachine\Discovery::getInstance());

// The registry now contains all the service providers discoverable by thecodingmachine/discovery.
```

Caching of `getFactories` and `getExtensions`
=============================================

You can use the shortcut `Registry::getFactories($key)` or `Registry::getExtensions($key)` methods to call the `getFactories` and `getExtensions` methods on a service provider. The result is cached: 2 successive calls will not call the `getFactories` or `getExtensions` methods twice.


```php
$factories = $registry->getFactories(0);

$extensions = $registry->getExtensions(0);
```

Using the registry to create services
=====================================

Even better, using the `createService` method of the registry, you can directly call the service factory:


```php
$myService = $registry->createService(0, 'serviceName', $container);
```

Using the `extendService` method of the registry, you can directly call the service extension:


```php
$myService = $registry->extendService(0, 'serviceName', $container, $previousService);
```

Why?
====

This was built for improving the performance of service providers loading (in compiled container environment).
