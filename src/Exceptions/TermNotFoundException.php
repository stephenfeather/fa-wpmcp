<?php

/**
 * Exception thrown when term is not found.
 *
 * @package FAWpmcp\Exceptions
 */

declare(strict_types=1);

namespace FAWpmcp\Exceptions;

use Exception;

/**
 * Exception for term not found errors.
 *
 * Thrown when get_term() returns null or WP_Error.
 *
 * @package FAWpmcp\Exceptions
 */
class TermNotFoundException extends Exception {

}
