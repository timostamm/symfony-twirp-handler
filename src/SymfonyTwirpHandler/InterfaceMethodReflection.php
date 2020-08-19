<?php


namespace SymfonyTwirpHandler;


use Google\Protobuf\Internal\Message;
use ReflectionMethod;

class InterfaceMethodReflection
{

    /** @var ReflectionMethod */
    private $reflectionMethod;

    /** @var string */
    private $parameterName;

    /** @var string */
    private $parameterType;

    /** @var string */
    private $returnType;


    /**
     * ReflectionServiceMethod constructor.
     * @param ReflectionMethod $reflectionMethod
     * @param string $parameterName
     * @param string $parameterType
     * @param string $returnType
     */
    public function __construct(ReflectionMethod $reflectionMethod, string $parameterName, string $parameterType, string $returnType)
    {
        $this->reflectionMethod = $reflectionMethod;
        $this->parameterName = $parameterName;
        $this->parameterType = $parameterType;
        $this->returnType = $returnType;
    }


    public function createParameterInstance(): Message
    {
        $type = $this->getParameterType();

        /** @var Message $parameter */
        $parameter = new $type();

        return $parameter;
    }


    public function getName(): string
    {
        return $this->reflectionMethod->getName();
    }


    public function getFullyQualifiedName()
    {
        return $this->getDeclaringInterface() . '::' . $this->getName();
    }


    public function getDeclaringInterface(): string
    {
        return $this->reflectionMethod->getDeclaringClass()->getName();
    }


    public function getParameterName(): string
    {
        return $this->parameterName;
    }


    public function getParameterType(): string
    {
        return $this->parameterType;
    }

    public function getReturnType(): string
    {
        return $this->returnType;
    }


}
