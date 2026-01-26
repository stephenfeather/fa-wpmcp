<?php
/**
 * Exception thrown when a comment is not found.
 *
 * @package FAWpmcp\Exceptions
 */

declare(strict_types=1);

namespace FAWpmcp\Exceptions;

use Exception;

/**
 * Exception for comment not found errors.
 *
 * Thrown when attempting to retrieve or update a comment that doesn't exist.
 *
 * @package FAWpmcp\Exceptions
 */
class CommentNotFoundException extends Exception {

}
