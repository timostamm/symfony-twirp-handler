<?php
/**
 * Created by PhpStorm.
 * User: ts
 * Date: 03.05.18
 * Time: 19:29
 */

namespace SymfonyTwirpHandler;


use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Throwable;


/**
 * Handles the TwirpError exception, all Exceptions implementing
 * HttpExceptionInterface, and Throwable.
 *
 * Sets a JsonResponse that contains proper Twirp error data.
 */
class TwirpErrorSubscriber implements EventSubscriberInterface
{

    /**
     * Register the exception event with this priority so
     * that the symfony firewall exception listener can
     * wrap security exceptions in http exceptions before
     * they reach us.
     *
     * @see \Symfony\Component\Security\Http\Firewall\ExceptionListener
     */
    const LISTENER_PRIORITY = 0;

    public static function getSubscribedEvents()
    {
        // return the subscribed events, their methods and priorities
        return array(
            KernelEvents::EXCEPTION => array(
                array(
                    'processException',
                    self::LISTENER_PRIORITY
                )
            )
        );
    }

    /** @var string|null */
    private $requestTagAttribute;

    /** @var bool */
    private $debug;

    /** @var string */
    private $prefix;


    /**
     * TwirpErrorSubscriber constructor.
     *
     * If you provide a $requestTagAttribute, the subscriber will look for a
     * request attribute with this name, and add it as "meta.request_tag".
     *
     * If you supply $debug = true, the stack trace of the exception is
     * added as "meta.stack".
     *
     * If the request URI path does not start with $prefix, the subscriber
     * will not process this request.
     *
     * @param string|null $requestTagAttribute
     * @param bool $debug
     * @param string $prefix
     */
    public function __construct(string $requestTagAttribute = null, bool $debug = false, string $prefix = "twirp")
    {
        $this->requestTagAttribute = $requestTagAttribute;
        $this->debug = $debug;
        $this->prefix = $prefix;
    }


    public function processException(ExceptionEvent $event)
    {
        if (!$this->isTwirpRequest($event->getRequest())) {
            return;
        }

        $exception = $event->getThrowable();

        if ($exception instanceof TwirpError) {

            $meta = $exception->getMeta();
            $this->addMetaRequestTag($meta, $event->getRequest());
            $this->addMetaStack($meta, $exception);
            $response = $this->makeResponse($exception->getMessage(), $exception->getErrorCode(), $meta);
            $event->setResponse($response);

        } else if ($exception instanceof HttpExceptionInterface) {

            $message = $exception->getMessage();
            if (empty($message)) {
                $message = "HTTP " . $exception->getStatusCode();
            }
            $code = TwirpError::INTERNAL;
            switch ($exception->getStatusCode()) {
                case Response::HTTP_UNAUTHORIZED:
                    $code = TwirpError::UNAUTHENTICATED;
                    break;
                case Response::HTTP_FORBIDDEN:
                    $code = TwirpError::PERMISSION_DENIED;
                    break;
            }
            $meta = [];
            $this->addMetaRequestTag($meta, $event->getRequest());
            $this->addMetaStack($meta, $exception);
            $response = $this->makeResponse($message, $code, $meta);
            $response->headers->add($exception->getHeaders());
            $event->setResponse($response);

        } else {

            $meta = [];
            $this->addMetaRequestTag($meta, $event->getRequest());
            $this->addMetaStack($meta, $exception);
            $response = $this->makeResponse($exception->getMessage(), TwirpError::INTERNAL, $meta);
            $event->setResponse($response);

        }
    }


    protected function isTwirpRequest(Request $request): bool
    {
        $prefix = $this->prefix;
        if (!str_starts_with($prefix, '/')) {
            $prefix = '/' . $prefix;
        }
        if (!str_ends_with($prefix, '/')) {
            $prefix = $prefix . '/';
        }
        $path = $request->getPathInfo();
        return str_starts_with($path, $prefix);
    }


    protected function makeResponse(string $msg, string $code, array $meta): JsonResponse
    {
        $json = [
            'msg' => $msg,
            'code' => $code,
        ];
        if (!empty($meta)) {
            $json['meta'] = $meta;
        }
        return new JsonResponse($json, $this->twirpCodeToHttp($code));
    }


    protected function addMetaStack(array &$meta, Throwable $throwable): void
    {
        if ($this->debug && empty($meta['stack'])) {
            $meta['stack'] = $throwable->__toString();
        }
    }


    protected function addMetaRequestTag(array &$meta, Request $request): void
    {
        if (!is_string($this->requestTagAttribute)) {
            return;
        }
        if (!$request->attributes->has($this->requestTagAttribute)) {
            return;
        }
        $tag = $request->attributes->get($this->requestTagAttribute);
        $meta['request_tag'] = $tag;
    }


    // https://twitchtv.github.io/twirp/docs/errors.html#error-codes
    protected function twirpCodeToHttp(string $twirpErrorCode): int
    {
        switch ($twirpErrorCode) {
            case TwirpError::CANCELLED:
            case TwirpError::DEADLINE_EXCEEDED:
                return Response::HTTP_REQUEST_TIMEOUT;

            case TwirpError::INVALID_ARGUMENT:
            case TwirpError::OUT_OF_RANGE:
            case TwirpError::MALFORMED:
                return Response::HTTP_BAD_REQUEST;

            case TwirpError::NOT_FOUND:
            case TwirpError::BAD_ROUTE:
                return Response::HTTP_NOT_FOUND;

            case TwirpError::ABORTED:
            case TwirpError::ALREADY_EXISTS:
                return Response::HTTP_CONFLICT;

            case TwirpError::UNAUTHENTICATED:
                return Response::HTTP_UNAUTHORIZED;

            case TwirpError::PERMISSION_DENIED:
            case TwirpError::RESOURCE_EXHAUSTED:
                return Response::HTTP_FORBIDDEN;

            case TwirpError::FAILED_PRECONDITION:
                return Response::HTTP_PRECONDITION_FAILED;

            case TwirpError::UNIMPLEMENTED:
                return Response::HTTP_NOT_IMPLEMENTED;

            case TwirpError::UNAVAILABLE:
                return Response::HTTP_SERVICE_UNAVAILABLE;

            case TwirpError::UNKNOWN:
            case TwirpError::INTERNAL:
            case TwirpError::DATALOSS:
            default:
                return Response::HTTP_INTERNAL_SERVER_ERROR;
        }
    }


}
