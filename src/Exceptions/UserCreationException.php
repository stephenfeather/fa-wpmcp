<?php

/**
 * Exception thrown when user creation fails.
 *
 * @package FAWpmcp\Exceptions
 */

declare(strict_types=1);

namespace FAWpmcp\Exceptions;

use Exception;

/**
 * Exception for user creation errors.
 *
 * Thrown when wp_insert_user() fails.
 *
 * @package FAWpmcp\Exceptions
 */
class UserCreationException extends Exception {

}
