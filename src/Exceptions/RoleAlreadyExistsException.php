<?php

/**
 * Exception thrown when attempting to create a role that already exists.
 *
 * @package FAWpmcp\Exceptions
 */

declare(strict_types=1);

namespace FAWpmcp\Exceptions;

use Exception;

/**
 * Exception for role already exists errors.
 *
 * Thrown when attempting to create a role with a name
 * that is already registered in WordPress.
 *
 * @package FAWpmcp\Exceptions
 */
class RoleAlreadyExistsException extends Exception
{
}
