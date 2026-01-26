<?php

/**
 * Exception thrown when media upload fails.
 *
 * @package FAWpmcp\Exceptions
 */

declare(strict_types=1);

namespace FAWpmcp\Exceptions;

use Exception;

/**
 * Exception for media upload failures.
 *
 * Thrown when file upload or attachment creation fails.
 *
 * @package FAWpmcp\Exceptions
 */
class MediaUploadException extends Exception
{
}
