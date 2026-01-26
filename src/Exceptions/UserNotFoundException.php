<?php
/**
 * Exception thrown when a user is not found.
 *
 * @package FAWpmcp\Exceptions
 */

declare(strict_types=1);

namespace FAWpmcp\Exceptions;

use Exception;

/**
 * Exception for user not found errors.
 *
 * Thrown when attempting to retrieve or update a user that doesn't exist.
 *
 * @package FAWpmcp\Exceptions
 */
class UserNotFoundException extends Exception {

}
