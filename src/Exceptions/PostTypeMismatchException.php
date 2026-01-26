<?php

/**
 * Exception thrown when post type doesn't match expected type.
 *
 * @package FAWpmcp\Exceptions
 */

declare(strict_types=1);

namespace FAWpmcp\Exceptions;

use Exception;

/**
 * Exception for post type mismatch errors.
 *
 * Thrown when a post exists but has a different type than requested.
 * For example, requesting a 'page' but the post is a 'post'.
 *
 * @package FAWpmcp\Exceptions
 */
class PostTypeMismatchException extends Exception
{
}
