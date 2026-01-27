<?php

/**
 * Exception thrown when a widget type is not found.
 *
 * @package FAWpmcp\Exceptions
 */

declare(strict_types=1);

namespace FAWpmcp\Exceptions;

use Exception;

/**
 * Exception for widget type not found errors.
 *
 * Thrown when attempting to create a widget using a widget type
 * (id_base) that does not exist in WordPress.
 *
 * @package FAWpmcp\Exceptions
 */
class WidgetTypeNotFoundException extends Exception
{
}
