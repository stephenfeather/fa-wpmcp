<?php
/**
 * Exception thrown when a role cannot be deleted.
 *
 * @package FAWpmcp\Exceptions
 */

declare(strict_types=1);

namespace FAWpmcp\Exceptions;

use Exception;

/**
 * Exception for role deletion errors.
 *
 * Thrown when attempting to delete a default WordPress role
 * or when role deletion fails for other reasons.
 *
 * @package FAWpmcp\Exceptions
 */
class RoleDeletionException extends Exception {

}
