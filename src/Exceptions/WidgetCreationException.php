<?php

/**
 * Exception thrown when widget creation fails.
 *
 * @package FAWpmcp\Exceptions
 */

declare(strict_types=1);

namespace FAWpmcp\Exceptions;

use Exception;

/**
 * Exception for widget creation errors.
 *
 * Thrown when a widget cannot be created, updated, or moved
 * due to invalid parameters or WordPress errors.
 *
 * @package FAWpmcp\Exceptions
 */
class WidgetCreationException extends Exception
{
}
