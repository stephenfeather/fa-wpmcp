<?php

/**
 * Exception thrown when a menu is not found.
 *
 * @package FAWpmcp\Exceptions
 */

declare(strict_types=1);

namespace FAWpmcp\Exceptions;

use Exception;

/**
 * Exception for menu not found errors.
 *
 * Thrown when attempting to access or modify a navigation menu
 * that does not exist in WordPress.
 *
 * @package FAWpmcp\Exceptions
 */
class MenuNotFoundException extends Exception
{
}
