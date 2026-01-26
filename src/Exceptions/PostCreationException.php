<?php
/**
 * Exception thrown when post creation fails.
 *
 * @package FAWpmcp\Exceptions
 */

declare(strict_types=1);

namespace FAWpmcp\Exceptions;

use Exception;

/**
 * Exception for post creation failures.
 *
 * Thrown when wp_insert_post() fails.
 *
 * @package FAWpmcp\Exceptions
 */
class PostCreationException extends Exception {

}
