<?php

/**
 * Exception thrown when menu creation fails.
 *
 * @package FAWpmcp\Exceptions
 */

declare(strict_types=1);

namespace FAWpmcp\Exceptions;

use Exception;

/**
 * Exception for menu creation errors.
 *
 * Thrown when attempting to create a navigation menu fails,
 * such as when the menu name already exists.
 *
 * @package FAWpmcp\Exceptions
 */
class MenuCreationException extends Exception {

}
