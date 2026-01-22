<?php
/**
 * Exception thrown when user update fails.
 *
 * @package FAWpmcp\Exceptions
 */

declare(strict_types=1);

namespace FAWpmcp\Exceptions;

use Exception;

/**
 * Exception for user update errors.
 *
 * Thrown when wp_update_user() fails.
 *
 * @package FAWpmcp\Exceptions
 */
class UserUpdateException extends Exception {
}
