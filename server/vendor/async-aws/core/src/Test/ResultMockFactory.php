<?php

declare(strict_types=1);

namespace AsyncAws\Core\Test;

use AsyncAws\Core\Exception\LogicException;
use AsyncAws\Core\Response;
use AsyncAws\Core\Result;
use AsyncAws\Core\Test\Http\SimpleMockedResponse;
use AsyncAws\Core\Waiter;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\MockHttpClient;

/**
 * An easy way to create Result objects for your tests.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class ResultMockFactory
{
    /**
     * Instantiate a Result class that throws exception.
     *
     * <code>
     * ResultMockFactory::createFailing(SendEmailResponse::class, 400, 'invalid value');
     * </code>
     *
     * @template T
     * @psalm-param class-string<T> $class
     *
     * @return Result|T
     */
    public static function createFailing(
        string $class,
        int $code,
        ?string $message = null,
        array $additionalContent = []
    ) {
        if (Result::class !== $class) {
            $parent = get_parent_class($class);
            if (false === $parent || Result::class !== $parent) {
                throw new LogicException(sprintf('The "%s::%s" can only be used for classes that extend "%s"', __CLASS__, __METHOD__, Result::class));
            }
        }

        $httpResponse = new SimpleMockedResponse(json_encode(array_merge(['message' => $message], $additionalContent)), ['content-type' => 'application/json'], $code);
        $client = new MockHttpClient($httpResponse);
        $response = new Response($client->request('POST', 'http://localhost'), $client, new NullLogger());

        $reflectionClass = new \ReflectionClass($class);

        return $reflectionClass->newInstance($response);
    }

    /**
     * Instantiate a Result class with some data.
     *
     * <code>
     * ResultMockFactory::create(SendEmailResponse::class, ['MessageId'=>'foo123']);
     * </code>
     *
     * @template T
     * @psalm-param class-string<T> $class
     *
     * @return Result|T
     */
    public static function create(string $class, array $data = [])
    {
        if (Result::class !== $class) {
            $parent = get_parent_class($class);
            if (false === $parent || Result::class !== $parent) {
                throw new LogicException(sprintf('The "%s::%s" can only be used for classes that extend "%s"', __CLASS__, __METHOD__, Result::class));
            }
        }

        $response = self::getResponseObject();

        // Make sure the Result is initialized
        $reflectionClass = new \ReflectionClass(Result::class);
        $initializedProperty = $reflectionClass->getProperty('initialized');
        $initializedProperty->setAccessible(true);

        $reflectionClass = new \ReflectionClass($class);
        $object = $reflectionClass->newInstance($response);
        if (Result::class !== $class) {
            self::addPropertiesOnResult($reflectionClass, $object, $class);
        }

        $initializedProperty->setValue($object, true);
        foreach ($data as $propertyName => $propertyValue) {
            if ($reflectionClass->hasProperty($propertyName)) {
                $property = $reflectionClass->getProperty($propertyName);
            } elseif ($reflectionClass->hasProperty(lcfirst($propertyName))) {
                // backward compatibility with `UpperCamelCase` naming (fast)
                $property = $reflectionClass->getProperty(lcfirst($propertyName));
            } else {
                // compatibility with new `wordWithABREV` naming (slow)
                $lowerPropertyName = strtolower($propertyName);
                $property = null;
                foreach ($reflectionClass->getProperties() as $prop) {
                    if (strtolower($prop->getName()) === $lowerPropertyName) {
                        $property = $prop;

                        break;
                    }
                }
                if (null === $property) {
                    // let bubble the original exception
                    $property = $reflectionClass->getProperty($propertyName);
                }
            }
            $property->setAccessible(true);
            $property->setValue($object, $propertyValue);
        }

        self::addUndefinedProperties($reflectionClass, $object, $data);

        return $object;
    }

    /**
     * Instantiate a Waiter class with a final state.
     *
     * @template T
     * @psalm-param class-string<T> $class
     *
     * @return Result|T
     */
    public static function waiter(string $class, string $finalState)
    {
        if (Result::class !== $class) {
            $parent = get_parent_class($class);
            if (false === $parent || Waiter::class !== $parent) {
                throw new LogicException(sprintf('The "%s::%s" can only be used for classes that extend "%s"', __CLASS__, __METHOD__, Waiter::class));
            }
        }

        if (Waiter::STATE_SUCCESS !== $finalState && Waiter::STATE_FAILURE !== $finalState) {
            throw new LogicException(sprintf('The state passed to "%s::%s" must be "%s" or "%s".', __CLASS__, __METHOD__, Waiter::STATE_SUCCESS, Waiter::STATE_FAILURE));
        }

        $response = self::getResponseObject();

        $reflectionClass = new \ReflectionClass(Waiter::class);
        $propertyResponse = $reflectionClass->getProperty('response');
        $propertyResponse->setAccessible(true);

        $propertyState = $reflectionClass->getProperty('finalState');
        $propertyState->setAccessible(true);

        $reflectionClass = new \ReflectionClass($class);
        $result = $reflectionClass->newInstanceWithoutConstructor();
        $propertyResponse->setValue($result, $response);
        $propertyState->setValue($result, $finalState);

        return $result;
    }

    /**
     * Try to add some values to the properties not defined in $data.
     *
     * @throws \ReflectionException
     */
    private static function addUndefinedProperties(\ReflectionClass $reflectionClass, $object, array $data): void
    {
        foreach ($reflectionClass->getProperties(\ReflectionProperty::IS_PRIVATE) as $property) {
            if (\array_key_exists($property->getName(), $data) || \array_key_exists(ucfirst($property->getName()), $data)) {
                continue;
            }

            if (!$reflectionClass->hasMethod('get' . $property->getName())) {
                continue;
            }

            $getter = $reflectionClass->getMethod('get' . $property->getName());
            if (!$getter->hasReturnType() || (!($type = $getter->getReturnType()) instanceof \ReflectionNamedType) || $type->allowsNull()) {
                continue;
            }

            switch ($type->getName()) {
                case 'int':
                    $propertyValue = 0;

                    break;
                case 'string':
                    $propertyValue = '';

                    break;
                case 'bool':
                    $propertyValue = false;

                    break;
                case 'float':
                    $propertyValue = 0.0;

                    break;
                case 'array':
                    $propertyValue = [];

                    break;
                default:
                    $propertyValue = null;

                    break;
            }

            if (null !== $propertyValue) {
                $property->setAccessible(true);
                $property->setValue($object, $propertyValue);
            }
        }
    }

    /**
     * Set input and aws client to handle pagination.
     */
    private static function addPropertiesOnResult(\ReflectionClass $reflectionClass, object $object, string $class): void
    {
        if (false === $pos = strrpos($class, '\\')) {
            throw new LogicException(sprintf('Expected class "%s" to have a backslash. ', $class));
        }

        $className = substr($class, $pos + 1);
        if ('Output' === substr($className, -6)) {
            $classNameWithoutSuffix = substr($className, 0, -6);
        } elseif ('Response' === substr($className, -8)) {
            $classNameWithoutSuffix = substr($className, 0, -8);
        } elseif ('Result' === substr($className, -6)) {
            $classNameWithoutSuffix = substr($className, 0, -6);
        } else {
            throw new LogicException(sprintf('Unknown class suffix: "%s"', $className));
        }

        if (false === $pos = strrpos($class, '\\', -2 - \strlen($className))) {
            throw new LogicException(sprintf('Expected class "%s" to have more than one backslash. ', $class));
        }

        $baseNamespace = substr($class, 0, $pos);
        if (false === $pos = strrpos($baseNamespace, '\\')) {
            throw new LogicException(sprintf('Expected base namespace "%s" to have a backslash. ', $baseNamespace));
        }

        $awsClientClass = $baseNamespace . (substr($baseNamespace, $pos)) . 'Client';
        $inputClass = $baseNamespace . '\\Input\\' . $classNameWithoutSuffix . 'Request';

        if (class_exists($awsClientClass)) {
            $awsClientMock = (new \ReflectionClass($awsClientClass))->newInstanceWithoutConstructor();
            $property = $reflectionClass->getProperty('awsClient');
            $property->setAccessible(true);
            $property->setValue($object, $awsClientMock);
        }

        if (class_exists($inputClass)) {
            $inputMock = (new \ReflectionClass($inputClass))->newInstanceWithoutConstructor();
            $property = $reflectionClass->getProperty('input');
            $property->setAccessible(true);
            $property->setValue($object, $inputMock);
        }
    }

    private static function getResponseObject(): Response
    {
        $reflectionClass = new \ReflectionClass(Response::class);
        $response = $reflectionClass->newInstanceWithoutConstructor();

        $property = $reflectionClass->getProperty('resolveResult');
        $property->setAccessible(true);
        $property->setValue($response, true);

        $property = $reflectionClass->getProperty('bodyDownloaded');
        $property->setAccessible(true);
        $property->setValue($response, true);

        return $response;
    }
}
