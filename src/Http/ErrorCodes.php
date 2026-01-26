<?php

/**
 * HTTP error codes and status mapping.
 *
 * Provides constants for standardized error codes and a mapping
 * to their corresponding HTTP status codes.
 *
 * @package FAWpmcp\Http
 */

declare(strict_types=1);

namespace FAWpmcp\Http;

/**
 * Error code constants and HTTP status mapping.
 *
 * This class provides a centralized definition of error codes used
 * throughout the application, along with their HTTP status code mappings.
 */
final class ErrorCodes
{
    /**
     * Error code for authentication required.
     *
     * @var string
     */
    public const AUTHENTICATION_REQUIRED = 'authentication_required';

    /**
     * Error code for insufficient permissions.
     *
     * @var string
     */
    public const INSUFFICIENT_PERMISSIONS = 'insufficient_permissions';

    /**
     * Error code for resource not found.
     *
     * @var string
     */
    public const NOT_FOUND = 'not_found';

    /**
     * Error code for validation errors.
     *
     * @var string
     */
    public const VALIDATION_ERROR = 'validation_error';

    /**
     * Error code for rate limit exceeded.
     *
     * @var string
     */
    public const RATE_LIMIT_EXCEEDED = 'rate_limit_exceeded';

    /**
     * Error code for internal server errors.
     *
     * @var string
     */
    public const INTERNAL_ERROR = 'internal_error';

    /**
     * Error code for method not allowed.
     *
     * @var string
     */
    public const METHOD_NOT_ALLOWED = 'method_not_allowed';

    /**
     * Error code for conflict.
     *
     * @var string
     */
    public const CONFLICT = 'conflict';

    /**
     * Mapping of error codes to HTTP status codes.
     *
     * @var array<string, int>
     */
    public const HTTP_STATUS_MAP = array(
        self::AUTHENTICATION_REQUIRED  => 401,
        self::INSUFFICIENT_PERMISSIONS => 403,
        self::NOT_FOUND                => 404,
        self::VALIDATION_ERROR         => 400,
        self::RATE_LIMIT_EXCEEDED      => 429,
        self::INTERNAL_ERROR           => 500,
        self::METHOD_NOT_ALLOWED       => 405,
        self::CONFLICT                 => 409,
    );
}
