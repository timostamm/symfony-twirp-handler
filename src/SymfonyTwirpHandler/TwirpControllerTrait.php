<?php


namespace SymfonyTwirpHandler;


use Google\Protobuf\Internal\Message;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


/**
 * Use this trait to very easily implement Twirp methods in a symfony
 * controller.
 */
trait TwirpControllerTrait
{

    /**
     * Parse a RPC input message from a symfony request.
     * The request can be in JSON or binary format.
     *
     * It is up to you to make sure that you pass the right
     * message class to this function.
     *
     * @param Request $request
     * @param string $inputMessageClass
     * @return Message
     */
    protected function readTwirp(Request $request, string $inputMessageClass): Message
    {

        /** @var Message $input */
        $input = new $inputMessageClass;

        // twirp only allows POST
        if ($request->getMethod() !== Request::METHOD_POST) {
            $msg = sprintf('Method %s not allowed.', $request->getMethod());
            throw new TwirpError($msg, TwirpError::BAD_ROUTE);
        }

        // parse input message
        if ($this->isJsonRequest($request)) {
            try {
                $input->mergeFromJsonString($request->getContent());
            } catch (\Exception $exception) {
                $msg = sprintf('Unable to deserialize %s from JSON format.', $inputMessageClass);
                throw new TwirpError($msg, TwirpError::MALFORMED, [], $exception);
            }
        } else if ($this->isProtobufRequest($request)) {
            try {
                $input->mergeFromString($request->getContent());
            } catch (\Exception $exception) {
                $msg = sprintf('Unable to deserialize %s from binary format.', $inputMessageClass);
                throw new TwirpError($msg, TwirpError::MALFORMED, [], $exception);
            }
        } else {
            $msg = "Missing content-type application/protobuf or application/json";
            throw new TwirpError($msg, TwirpError::MALFORMED, [], null);
        }

        return $input;
    }


    /**
     * Write a RPC output message to a symfony request.
     * Automatically creates a JSON or binary response.
     *
     * @param Request $request
     * @param Message $output
     * @return Response
     */
    protected function writeTwirp(Request $request, Message $output): Response
    {
        // serialize response
        if ($this->isJsonRequest($request)) {
            $json = $output->serializeToJsonString();
            $response = new JsonResponse();
            $response->setJson($json);
            return $response;
        }
        $data = $output->serializeToString();
        $response = new Response($data);
        $response->headers->set('Content-Type', 'application/protobuf');
        return $response;
    }


    private function isJsonRequest(Request $request): bool
    {
        return $request->headers->get('CONTENT_TYPE') === 'application/json';
    }

    private function isProtobufRequest(Request $request): bool
    {
        return $request->headers->get('CONTENT_TYPE') === 'application/protobuf';
    }


}
