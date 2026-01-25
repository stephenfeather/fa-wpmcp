<?php
/**
 * Exception thrown when a role assignment is not allowed.
 *
 * @package FAWpmcp\Exceptions
 */

declare(strict_types=1);

namespace FAWpmcp\Exceptions;

use Exception;

/**
 * Exception for role assignment restrictions.
 *
 * Thrown when attempting to assign a role that exceeds the
 * maximum allowed role configured via fa_wpmcp_max_api_role.
 *
 * @package FAWpmcp\Exceptions
 */
class RoleNotAllowedException extends Exception {
}
