<?php

/**
 * Exception thrown when media item is not found.
 *
 * @package FAWpmcp\Exceptions
 */

declare(strict_types=1);

namespace FAWpmcp\Exceptions;

use Exception;

/**
 * Exception for media not found errors.
 *
 * Thrown when get_post() returns null for a media ID
 * or when the post is not an attachment type.
 *
 * @package FAWpmcp\Exceptions
 */
class MediaNotFoundException extends Exception {

}
