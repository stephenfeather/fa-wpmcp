<?php

/**
 * Exception thrown when a widget is not found.
 *
 * @package FAWpmcp\Exceptions
 */

declare(strict_types=1);

namespace FAWpmcp\Exceptions;

use Exception;

/**
 * Exception for widget not found errors.
 *
 * Thrown when attempting to access or modify a widget
 * that does not exist in WordPress.
 *
 * @package FAWpmcp\Exceptions
 */
class WidgetNotFoundException extends Exception
{
}
