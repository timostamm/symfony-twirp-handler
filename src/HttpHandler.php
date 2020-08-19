<?php /** @noinspection PhpUnusedParameterInspection */


namespace SymfonyTwirpHandler;


use Exception;
use Google\Protobuf\Internal\DescriptorPool;
use Google\Protobuf\Internal\Message;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class HttpHandler
{


    /** @var ServiceResolver */
    private $resolver;

    /** @var bool */
    private $serviceNamesCaseSensitive = false;

    /** @var bool */
    private $methodNamesCaseSensitive = false;

    /** @var LoggerInterface */
    private $logger;

    /** @var bool */
    private $debug = false;

    private $allowedRequestMethods = [
        Request::METHOD_PATCH,
        Request::METHOD_POST,
        Request::METHOD_PUT,
    ];

    private $jsonContentTypes = [
        'application/json'
    ];

    private $protoContentTypes = [
        'application/protobuf'
    ];


    /**
     * ServiceHandler constructor.
     * @param ServiceResolver $serviceResolver
     */
    public function __construct(ServiceResolver $serviceResolver)
    {
        $this->resolver = $serviceResolver;
        $this->logger = new NullLogger();
    }


    /**
     * @param string $serviceName
     * @param string $methodName
     * @param Request $request
     * @return Response
     */
    public function handle(string $serviceName, string $methodName, Request $request): Response
    {
        $service = $this->resolver->findService($serviceName, $this->isServiceNamesCaseSensitive());
        if (!$service) {
            return $this->makeServiceNotFoundResponse($serviceName, $methodName, $request);
        }
        $method = $service->findMethod($methodName, $this->isMethodNamesCaseSensitive());
        if (!$method) {
            return $this->makeMethodNotFoundResponse($service, $methodName, $request);
        }
        if (!in_array($request->getMethod(), $this->getAllowedRequestMethods())) {
            return $this->makeRequestMethodNotAllowedResponse($service, $methodName, $request);
        }
        $parameter = $method->createParameterInstance();
        $intercept = $this->parseParameter($parameter, $method, $request);
        if ($intercept) {
            return $intercept;
        }
        $return = $method->invoke($parameter);
        return $this->makeReturnResponse($return, $method, $request);
    }


    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }


    public function isDebug(): bool
    {
        return $this->debug;
    }

    public function setDebug(bool $debug): void
    {
        $this->debug = $debug;
    }


    public function isServiceNamesCaseSensitive(): bool
    {
        return $this->serviceNamesCaseSensitive;
    }

    public function setServiceNamesCaseSensitive(bool $serviceNamesCaseSensitive): void
    {
        $this->serviceNamesCaseSensitive = $serviceNamesCaseSensitive;
    }


    public function isMethodNamesCaseSensitive(): bool
    {
        return $this->methodNamesCaseSensitive;
    }

    public function setMethodNamesCaseSensitive(bool $methodNamesCaseSensitive): void
    {
        $this->methodNamesCaseSensitive = $methodNamesCaseSensitive;
    }


    /**
     * @return string[]
     */
    public function getAllowedRequestMethods(): array
    {
        return $this->allowedRequestMethods;
    }

    public function setAllowedRequestMethods(string ...$allowedRequestMethods): void
    {
        $this->allowedRequestMethods = $allowedRequestMethods;
    }

    /**
     * @return string[]
     */
    public function getJsonContentTypes(): array
    {
        return $this->jsonContentTypes;
    }

    public function setJsonContentTypes(string ...$jsonContentTypes): void
    {
        $this->jsonContentTypes = $jsonContentTypes;
    }

    /**
     * @return string[]
     */
    public function getProtoContentTypes(): array
    {
        return $this->protoContentTypes;
    }


    public function setProtoContentTypes(string ...$protoContentTypes): void
    {
        $this->protoContentTypes = $protoContentTypes;
    }


    protected function parseParameter(Message $parameter, ImplementationMethodReflection $method, Request $request): ?Response
    {
        try {
            if ($this->isJsonRequest($request)) {
                $parameter->mergeFromJsonString($request->getContent());
            } else {
                $parameter->mergeFromString($request->getContent());
            }
        } catch (Exception $exception) {
            $this->logInvalidParameter($method, $request, $exception);
            return $this->makeInvalidParameterResponse($method, $request, $exception);
        }
        return null;
    }


    protected function logInvalidParameter(ImplementationMethodReflection $method, Request $request, Exception $exception): void
    {
        $this->logger->error('Bad Request for {method}: {exceptionClass} in {exceptionFile}:{exceptionLine}: {exceptionMessage}', [
            'method' => $method->getFullyQualifiedName(),
            'parameterName' => $method->getParameterName(),
            'parameterType' => $method->getParameterType(),
            'exceptionClass' => get_class($exception),
            'exceptionFile' => $exception->getFile(),
            'exceptionLine' => $exception->getLine(),
            'exceptionMessage' => $exception->getMessage(),
        ]);
    }


    protected function makeInvalidParameterResponse(ImplementationMethodReflection $method, Request $request, Exception $exception): Response
    {
        $content = 'Bad Request';
        if ($this->isDebug()) {
            $content .= "\n\n" . sprintf('Invalid parameter "%s" for method %s.', $method->getParameterName(), $method->getFullyQualifiedName());
            $content .= "\n\n" . $exception->__toString();
        }
        return new Response($content, Response::HTTP_BAD_REQUEST, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }


    protected function makeServiceNotFoundResponse(string $serviceName, string $methodName, Request $request): Response
    {
        $content = sprintf('Resource not found');
        if ($this->isDebug()) {
            $content .= "\n\n" . "Available services: \n";
            foreach ($this->resolver->getServices() as $service) {
                $content .= sprintf("   %s\n", $service->getName());
            }
        }
        return new Response($content, Response::HTTP_NOT_FOUND, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }


    protected function makeMethodNotFoundResponse(ImplementationReflection $implementation, string $methodName, Request $request): Response
    {
        $content = sprintf('Resource not found');
        if ($this->isDebug()) {
            $content .= "\n\n" . "Service: " . $implementation->getName();
            $content .= "\n\n" . "Available methods: \n";
            foreach ($implementation->getMethods() as $method) {
                $content .= sprintf("   %s(%s %s): %s\n", $method->getName(), $method->getParameterType(), $method->getParameterName(), $method->getReturnType());
            }
        }
        return new Response($content, Response::HTTP_NOT_FOUND, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }


    protected function makeRequestMethodNotAllowedResponse(ImplementationReflection $implementation, string $methodName, Request $request): Response
    {
        $content = sprintf('Method %s not allowed.', $request->getMethod());
        if ($this->isDebug()) {
            $content .= "\n\n" . "Service: " . $implementation->getName();
            $content .= "\n\n" . "Allowed request methods: " . join(', ', $this->allowedRequestMethods);
        }
        return new Response($content, Response::HTTP_METHOD_NOT_ALLOWED, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }


    protected function makeReturnResponse(Message $return, ImplementationMethodReflection $method, Request $request): Response
    {
        return $this->shouldJsonResponse($request)
            ? $this->makeJsonResponse($return, $method, $request)
            : $this->makeBinaryResponse($return, $method, $request);
    }


    protected function makeBinaryResponse(Message $return, ImplementationMethodReflection $method, Request $request): Response
    {
        $contentType = 'application/protobuf; proto=' . $this->getProtoName($return);
        $data = $return->serializeToString();
        $response = new Response($data);
        $response->headers->set('Content-Type', $contentType);
        return $response;
    }


    protected function makeJsonResponse(Message $return, ImplementationMethodReflection $method, Request $request): Response
    {
        $json = $return->serializeToJsonString();
        $response = new JsonResponse();
        $response->setJson($json);
        return $response;
    }


    protected function shouldJsonResponse(Request $request): bool
    {
        $acceptsProto = false;
        $acceptsJson = false;
        foreach ($request->getAcceptableContentTypes() as $accept) {
            if (in_array($accept, $this->getProtoContentTypes())) {
                $acceptsProto = true;
            }
        }
        foreach ($request->getAcceptableContentTypes() as $accept) {
            if (in_array($accept, $this->getJsonContentTypes())) {
                $acceptsJson = true;
            }
        }
        if ($acceptsProto && $acceptsJson) {
            return $this->isJsonRequest($request);
        }
        if ($acceptsJson && !$acceptsProto) {
            return true;
        }
        return $this->isJsonRequest($request);
    }


    protected function isJsonRequest(Request $request): bool
    {
        return in_array($request->headers->get('CONTENT_TYPE'), $this->getJsonContentTypes());
    }


    protected function getProtoName(Message $message): string
    {
        $pool = DescriptorPool::getGeneratedPool();
        $desc = $pool->getDescriptorByClassName(get_class($message));
        return $desc->getFullName();
    }


}
