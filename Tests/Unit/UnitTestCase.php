<?php

namespace Lightwerk\SurfTasks\Tests\Unit;

/**
 * Class UnitTestCase
 *
 * @author Daniel Goerz <dlg@lightwerk.com>
 */
class UnitTestCase extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * Returns an accessible \ReflectionMethod object of the $method
     * on $class. The method can then be called in tests with
     *
     * $method->invokeArgs($object, [$argument1, $argument2])
     *
     * @param string $method
     * @param mixed $object
     * @return \ReflectionMethod
     */
    protected function getAccessiblePrivateMethodForObject(string $method, $object): \ReflectionMethod
    {
        $nodeFactoryReflection = new \ReflectionClass($object);
        $method = $nodeFactoryReflection->getMethod($method);
        $method->setAccessible(true);
        return $method;
    }
}