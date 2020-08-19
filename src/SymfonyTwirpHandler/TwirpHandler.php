<?php


namespace SymfonyTwirpHandler;


use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;


class TwirpHandler
{

    /** @var ServiceResolver */
    private $resolver;

    /**
     * ServiceHandler constructor.
     * @param ServiceResolver $serviceResolver
     */
    public function __construct(ServiceResolver $serviceResolver)
    {
        $this->resolver = $serviceResolver;
    }


    /**
     * @param string $serviceName
     * @param string $methodName
     * @param Request $request
     * @return Response
     */
    public function handle(string $serviceName, string $methodName, Request $request): Response
    {

        // locate service and method
        $service = $this->resolver->findService($serviceName, false);
        if (!$service) {
            $msg = sprintf('Service is unknown.');
            throw new TwirpError($msg, TwirpError::BAD_ROUTE);
        }
        $method = $service->findMethod($methodName, false);
        if (!$method) {
            $msg = sprintf('Method %s is unknown for service %s.', $method, $service->getName());
            throw new TwirpError($msg, TwirpError::BAD_ROUTE);
        }

        // twirp only allows POST
        if ($request->getMethod() !== Request::METHOD_POST) {
            $msg = sprintf('Method %s not allowed.', $request->getMethod());
            throw new TwirpError($msg, TwirpError::BAD_ROUTE);
        }

        // create input message instance
        try {
            $parameter = $method->createParameterInstance();
        } catch (Exception $exception) {
            $msg = sprintf('Internal request parse error.');
            throw new TwirpError($msg, TwirpError::INTERNAL, [], $exception);
        }

        // parse input message
        if ($this->isJsonRequest($request)) {
            try {
                $parameter->mergeFromJsonString($request->getContent());
            } catch (Exception $exception) {
                $msg = sprintf('Unable to deserialize %s from JSON format.', $method->getParameterType());
                throw new TwirpError($msg, TwirpError::MALFORMED, [], $exception);
            }
        } else if ($this->isProtobufRequest($request)) {
            try {
                $parameter->mergeFromString($request->getContent());
            } catch (Exception $exception) {
                $msg = sprintf('Unable to deserialize %s from binary format.', $method->getParameterType());
                throw new TwirpError($msg, TwirpError::MALFORMED, [], $exception);
            }
        } else {
            $msg = "Missing content-type application/protobuf or application/json";
            throw new TwirpError($msg, TwirpError::MALFORMED, [], null);
        }

        // invoke service
        try {
            $output = $method->invoke($parameter);
        } catch (TwirpError $exception) {
            throw $exception;
        } catch (Throwable $throwable) {
            $msg = "Internal service error";
            throw new TwirpError($msg, TwirpError::INTERNAL, [], $throwable);
        }

        // serialize response
        if ($this->isJsonRequest($request)) {
            try {
                $json = $output->serializeToJsonString();
                $response = new JsonResponse();
                $response->setJson($json);
                return $response;
            } catch (Exception $exception) {
                $msg = sprintf('Unable to serialize %s to JSON format.', $method->getReturnType());
                throw new TwirpError($msg, TwirpError::INTERNAL, [], $exception);
            }
        }
        try {
            $data = $output->serializeToString();
            $response = new Response($data);
            $response->headers->set('Content-Type', 'application/protobuf');
            return $response;
        } catch (Exception $exception) {
            $msg = sprintf('Unable to serialize %s to binary format.', $method->getReturnType());
            throw new TwirpError($msg, TwirpError::INTERNAL, [], $exception);
        }
    }


    protected function isJsonRequest(Request $request): bool
    {
        return $request->headers->get('CONTENT_TYPE') === 'application/json';
    }

    protected function isProtobufRequest(Request $request): bool
    {
        return $request->headers->get('CONTENT_TYPE') === 'application/protobuf';
    }


}
