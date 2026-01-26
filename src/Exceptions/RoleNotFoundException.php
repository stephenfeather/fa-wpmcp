<?php

/**
 * Exception thrown when a role is not found.
 *
 * @package FAWpmcp\Exceptions
 */

declare(strict_types=1);

namespace FAWpmcp\Exceptions;

use Exception;

/**
 * Exception for role not found errors.
 *
 * Thrown when attempting to access or modify a role
 * that does not exist in WordPress.
 *
 * @package FAWpmcp\Exceptions
 */
class RoleNotFoundException extends Exception {

}
