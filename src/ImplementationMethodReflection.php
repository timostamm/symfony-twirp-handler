<?php


namespace SymfonyTwirpHandler;


use Google\Protobuf\Internal\Message;
use LogicException;
use ReflectionException;
use ReflectionMethod;
use RuntimeException;
use Throwable;
use UnexpectedValueException;

class ImplementationMethodReflection
{


    /** @var InterfaceMethodReflection */
    private $interfaceMethod;

    /** @var object */
    private $implementation;


    /**
     * ReflectionServiceMethod constructor.
     * @param InterfaceMethodReflection $method
     * @param object $implementation The instance implementing the service interface
     */
    public function __construct(InterfaceMethodReflection $method, $implementation)
    {
        $this->interfaceMethod = $method;
        $this->implementation = $implementation;
    }


    /**
     * @param $parameter Message The method parameter, which must be of the type provided by getParameterType()
     * @return Message The result of the invocation
     */
    public function invoke(Message $parameter): Message
    {

        if (!is_a($parameter, $this->getParameterType(), true)) {
            $msg = sprintf('Expected parameter to be a %s. Got a %s instead.', $this->getParameterType(), get_class($parameter));
            throw new UnexpectedValueException($msg);
        }

        try {
            $method = new ReflectionMethod($this->implementation, $this->getName());
        } catch (ReflectionException $e) {
            $msg = sprintf('Unable to reflect method %s().', $this->getFullyQualifiedName());
            throw new LogicException($msg);
        }

        $return = $method->invoke($this->implementation, $parameter);

        if (is_null($return)) {
            $msg = sprintf('Faulty service implementation. Expected return value of %s() to be a %s. Got NULL instead.', $this->getFullyQualifiedName(), $this->getReturnType());
            throw new UnexpectedValueException($msg);
        }
        if (!is_object($return)) {
            $msg = sprintf('Faulty service implementation. Expected return value of %s() to be a %s. Got %s instead.', $this->getFullyQualifiedName(), $this->getReturnType(), gettype($return));
            throw new UnexpectedValueException($msg);
        }
        if (!is_a($return, $this->getReturnType(), true)) {
            $msg = sprintf('Faulty service implementation. Expected return value of %s() to be a %s. Got a %s instead.', $this->getFullyQualifiedName(), $this->getReturnType(), get_class($parameter));
            throw new UnexpectedValueException($msg);
        }
        return $return;
    }


    public function createParameterInstance(): Message
    {
        return $this->interfaceMethod->createParameterInstance();
    }


    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->interfaceMethod->getName();
    }


    public function getFullyQualifiedName()
    {
        $class = get_class($this->implementation);
        return $class . '::' . $this->getName();
    }


    public function getDeclaringImplementation()
    {
        return get_class($this->implementation);
    }


    public function getDeclaringInterface()
    {
        return $this->interfaceMethod->getDeclaringInterface();
    }


    /**
     * @return string
     */
    public function getParameterName(): string
    {
        return $this->interfaceMethod->getParameterName();
    }

    /**
     * @return string
     */
    public function getParameterType(): string
    {
        return $this->interfaceMethod->getParameterType();
    }

    /**
     * @return string
     */
    public function getReturnType(): string
    {
        return $this->interfaceMethod->getReturnType();
    }


}
