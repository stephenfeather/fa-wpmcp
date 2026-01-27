<?php

/**
 * Exception thrown when a sidebar is not found.
 *
 * @package FAWpmcp\Exceptions
 */

declare(strict_types=1);

namespace FAWpmcp\Exceptions;

use Exception;

/**
 * Exception for sidebar not found errors.
 *
 * Thrown when attempting to access or modify a widget sidebar
 * that does not exist in WordPress.
 *
 * @package FAWpmcp\Exceptions
 */
class SidebarNotFoundException extends Exception
{
}
