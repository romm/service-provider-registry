<?php

namespace TheCodingMachine\ServiceProvider;

use Mouf\Picotainer\Picotainer;
use TheCodingMachine\Discovery\DiscoveryInterface;
use TheCodingMachine\ServiceProvider\Fixtures\TestServiceProvider;
use TheCodingMachine\ServiceProvider\Fixtures\TestStatefulServiceProvider;

class ServiceProviderRegistryTest extends \PHPUnit_Framework_TestCase
{
    public function testRegistry()
    {
        $registry = new Registry([
            TestServiceProvider::class,
        ]);

        $this->assertEquals(new TestServiceProvider(), $registry[0]);
    }

    public function testRegistryInjectInstance()
    {
        $registry = new Registry([
            new TestServiceProvider(),
        ]);

        $this->assertEquals(new TestServiceProvider(), $registry[0]);
        $this->assertSame($registry[0], $registry[0]);
    }

    public function testRegistryArrayWithNoParams()
    {
        $registry = new Registry([
            [TestStatefulServiceProvider::class],
        ]);

        $this->assertInstanceOf(TestStatefulServiceProvider::class, $registry[0]);
        $this->assertEquals(null, $registry[0]->foo);
    }

    public function testRegistryArrayWithParams()
    {
        $registry = new Registry([
            [TestStatefulServiceProvider::class, [42]],
        ]);

        $this->assertInstanceOf(TestStatefulServiceProvider::class, $registry[0]);
        $this->assertEquals(42, $registry[0]->foo);
    }

    public function testUnset()
    {
        $registry = new Registry([
            TestServiceProvider::class,
        ]);

        $this->assertArrayHasKey(0, $registry);
        unset($registry[0]);
        $this->assertArrayNotHasKey(0, $registry);
    }

    public function testPush()
    {
        $registry = new Registry();

        $key = $registry->push(TestStatefulServiceProvider::class, 42);
        $this->assertArrayHasKey($key, $registry);
        $this->assertInstanceOf(TestStatefulServiceProvider::class, $registry[$key]);
        $this->assertEquals(42, $registry[$key]->foo);
    }

    public function testPushObject()
    {
        $registry = new Registry();

        $key = $registry->push(new TestServiceProvider());
        $this->assertArrayHasKey($key, $registry);
        $this->assertInstanceOf(TestServiceProvider::class, $registry[$key]);
    }

    /**
     * @expectedException \TheCodingMachine\ServiceProvider\InvalidArgumentException
     */
    public function testPushException()
    {
        $registry = new Registry();

        $registry->push(array());
    }

    /**
     * @expectedException \LogicException
     */
    public function testSet()
    {
        $registry = new Registry();

        $registry[0] = 12;
    }

    public function testTcmDiscovery()
    {
        $discovery = new class implements DiscoveryInterface {
            public function get(string $assetType): array
            {
                return [ TestServiceProvider::class ];
            }
        };

        $registry = new Registry([], $discovery);

        $serviceProvider = $registry[0];
        $this->assertInstanceOf(TestServiceProvider::class, $serviceProvider);
    }

    public function testGetServices()
    {
        $registry = new Registry([
            new TestServiceProvider(),
        ]);

        $services = $registry->getFactories(0);
        $this->assertArrayHasKey('serviceA', $services);

        $services2 = $registry->getFactories(0);

        $this->assertSame($services['serviceA'], $services2['serviceA']);
    }

    public function testExtendServices()
    {
        $registry = new Registry([
            new TestServiceProvider(),
        ]);

        $services = $registry->getExtensions(0);
        $this->assertArrayHasKey('serviceB', $services);

        $services2 = $registry->getExtensions(0);

        $this->assertSame($services['serviceB'], $services2['serviceB']);
    }

    public function testGetServiceFactory()
    {
        $registry = new Registry([
            new TestServiceProvider(),
        ]);

        $service = $registry->createService(0, 'param', new Picotainer([]));

        $this->assertEquals(42, $service);
    }

    public function testGetServiceExtension()
    {
        $registry = new Registry([
            new TestServiceProvider(),
        ]);

        $service = $registry->extendService(0, 'serviceB', new Picotainer([]), null);

        $this->assertInstanceOf(\stdClass::class, $service);
    }

    public function testIterator()
    {
        $registry = new Registry([
            TestServiceProvider::class,
            TestServiceProvider::class,
        ]);

        $i = 0;
        foreach ($registry as $key => $serviceProvider) {
            $this->assertEquals($i, $key);
            $this->assertInstanceOf(TestServiceProvider::class, $serviceProvider);
            $i++;
        }
    }
}
