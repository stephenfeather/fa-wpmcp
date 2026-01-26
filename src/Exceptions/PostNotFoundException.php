<?php

/**
 * Exception thrown when a post is not found.
 *
 * @package FAWpmcp\Exceptions
 */

declare(strict_types=1);

namespace FAWpmcp\Exceptions;

use Exception;

/**
 * Exception for post not found errors.
 *
 * Thrown when attempting to retrieve or update a post that doesn't exist.
 *
 * @package FAWpmcp\Exceptions
 */
class PostNotFoundException extends Exception {

}
