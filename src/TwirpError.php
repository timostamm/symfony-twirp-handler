<?php


namespace SymfonyTwirpHandler;


use RuntimeException;
use Throwable;


/**
 * See https://twitchtv.github.io/twirp/docs/spec_v5.html#error-codes
 *
 * Names and descriptions from [twirp / errors.go](https://github.com/twitchtv/twirp/blob/b2ecb97cf02a9bb55d730920f6a1cb5243899093/errors.go)
 *
 * Copyright 2018 by Twitch Interactive, Inc.
 */
class TwirpError extends RuntimeException
{

    /**
     * The operation was cancelled.
     */
    const CANCELLED = "cancelled";

    /**
     * An unknown error occurred. For example, this can be used when handling
     * errors raised by APIs that do not return any error information.
     */
    const UNKNOWN = "unknown";

    /**
     * The client specified an invalid argument. This indicates arguments that
     * are invalid regardless of the state of the system (i.e. a malformed
     * file name, required argument, number out of range, etc.).
     */
    const INVALID_ARGUMENT = "invalid_argument";

    /**
     * The client sent a message which could not be decoded. This may mean that
     * the message was encoded improperly or that the client and server have
     * incompatible message definitions.
     */
    const MALFORMED = "malformed";

    /**
     * Operation expired before completion. For operations that change the state
     * of the system, this error may be returned even if the operation has
     * completed successfully (timeout).
     */
    const DEADLINE_EXCEEDED = "deadline_exceeded";

    /**
     * Some requested entity was not found.
     */
    const NOT_FOUND = "not_found";

    /**
     * The requested URL path wasn't routable to a Twirp service and method.
     * This is returned by generated server code and should not be returned
     * by application code (use "not_found" or "unimplemented" instead).
     */
    const BAD_ROUTE = "bad_route";

    /**
     * An attempt to create an entity failed because one already exists.
     */
    const ALREADY_EXISTS = "already_exists";

    /**
     * The caller does not have permission to execute the specified operation.
     * It must not be used if the caller cannot be identified (use
     * "unauthenticated" instead).
     */
    const PERMISSION_DENIED = "permission_denied";

    /**
     * The request does not have valid authentication credentials for the
     * operation.
     */
    const UNAUTHENTICATED = "unauthenticated";

    /**
     * Some resource has been exhausted, perhaps a per-user quota, or
     * perhaps the entire file system is out of space.
     */
    const RESOURCE_EXHAUSTED = "resource_exhausted";

    /**
     * The operation was rejected because the system is not in a state
     * required for the operation's execution. For example, doing an rmdir
     * operation on a directory that is non-empty, or on a non-directory
     * object, or when having conflicting read-modify-write on the same
     * resource.
     */
    const FAILED_PRECONDITION = "failed_precondition";

    /**
     * The operation was aborted, typically due to a concurrency issue
     * like sequencer check failures, transaction aborts, etc.
     */
    const ABORTED = "aborted";

    /**
     * The operation was attempted past the valid range. For example, seeking
     * or reading past end of a paginated collection. Unlike
     * "invalid_argument", this error indicates a problem that may be fixed if
     * the system state changes (i.e. adding more items to the collection).
     * There is a fair bit of overlap between "failed_precondition" and
     * "out_of_range". We recommend using "out_of_range" (the more specific
     * error) when it applies so that callers who are iterating through a space
     * can easily look for an "out_of_range" error to detect when they are done.
     */
    const OUT_OF_RANGE = "out_of_range";

    /**
     * The operation is not implemented or not supported/enabled in this service.
     */
    const UNIMPLEMENTED = "unimplemented";

    /**
     * When some invariants expected by the underlying system have been broken.
     * In other words, something bad happened in the library or backend service.
     * Twirp specific issues like wire and serialization problems are also
     * reported as "internal" errors.
     */
    const INTERNAL = "internal";

    /**
     * The service is currently unavailable. This is most likely a transient
     * condition and may be corrected by retrying with a backoff.
     */
    const UNAVAILABLE = "unavailable";

    /**
     * The operation resulted in unrecoverable data loss or corruption.
     */
    const DATALOSS = "dataloss";



    /** @var array */
    private $meta;

    /** @var string */
    private $errorCode;


    public function __construct(string $message, string $twirpErrorCode = TwirpError::UNKNOWN, array $meta = [], Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->errorCode = $twirpErrorCode;
        $this->meta = $meta;
    }

    public function getMeta(): array
    {
        return $this->meta;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }


}
