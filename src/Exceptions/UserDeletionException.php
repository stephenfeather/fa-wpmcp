<?php

/**
 * Exception thrown when user deletion fails.
 *
 * @package FAWpmcp\Exceptions
 */

declare(strict_types=1);

namespace FAWpmcp\Exceptions;

use Exception;

/**
 * Exception for user deletion failures.
 *
 * Thrown when wp_delete_user() fails or when attempting
 * to delete a protected user (e.g., self-deletion).
 *
 * @package FAWpmcp\Exceptions
 */
class UserDeletionException extends Exception {

}
