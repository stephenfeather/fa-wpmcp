<?php

/**
 * BulkModerateComments ability - moderate multiple comments at once.
 *
 * @package FAWpmcp\Abilities\Comments
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Comments;

use FAWpmcp\Abilities\AbstractAbility;

/**
 * Ability to moderate multiple WordPress comments at once.
 *
 * Supports approve, unapprove (hold), spam, unspam, trash, and untrash actions.
 *
 * @package FAWpmcp\Abilities\Comments
 */
final class BulkModerateComments extends AbstractAbility
{
    /**
     * Valid moderation actions.
     *
     * @var array<string>
     */
    private const VALID_ACTIONS = array(
        'approve',
        'unapprove',
        'spam',
        'unspam',
        'trash',
        'untrash',
    );

    /**
     * Returns the ability identifier.
     *
     * @return string Ability name.
     */
    public function getName(): string
    {
        return 'fa-wpmcp/bulk-moderate-comments';
    }

    /**
     * Returns the ability category.
     *
     * @return string Category name.
     */
    public function getCategory(): string
    {
        return 'comments';
    }

    /**
     * Returns the display label.
     *
     * @return string Ability label.
     */
    public function getLabel(): string
    {
        return 'Bulk Moderate Comments';
    }

    /**
     * Returns the ability description.
     *
     * @return string Description.
     */
    public function getDescription(): string
    {
        return 'Moderate multiple comments at once. Actions: approve, unapprove, spam, unspam, trash, untrash.';
    }

    /**
     * Returns the operation type.
     *
     * @return string Operation type (read or write).
     */
    public function getOperationType(): string
    {
        return 'write';
    }

    /**
     * Returns the JSON Schema for input validation.
     *
     * @return array<string, mixed> JSON Schema array.
     */
    public function getInputSchema(): array
    {
        return array(
            'type'       => 'object',
            'properties' => array(
                'comment_ids' => array(
                    'type'        => 'array',
                    'description' => 'Array of comment IDs to moderate.',
                    'items'       => array(
                        'type'    => 'integer',
                        'minimum' => 1,
                    ),
                    'minItems' => 1,
                    'maxItems' => 100,
                ),
                'action' => array(
                    'type'        => 'string',
                    'description' => 'The moderation action: approve, unapprove, spam, unspam, trash, untrash.',
                    'enum'        => self::VALID_ACTIONS,
                ),
            ),
            'required'   => array('comment_ids', 'action'),
        );
    }

    /**
     * Returns the JSON Schema for output.
     *
     * @return array<string, mixed> JSON Schema array.
     */
    public function getOutputSchema(): array
    {
        return array(
            'type'       => 'object',
            'properties' => array(
                'action'    => array('type' => 'string'),
                'processed' => array('type' => 'integer'),
                'succeeded' => array('type' => 'integer'),
                'failed'    => array('type' => 'integer'),
                'results'   => array(
                    'type'  => 'array',
                    'items' => array(
                        'type'       => 'object',
                        'properties' => array(
                            'comment_id' => array('type' => 'integer'),
                            'success'    => array('type' => 'boolean'),
                            'error'      => array('type' => 'string'),
                        ),
                    ),
                ),
            ),
        );
    }

    /**
     * Returns the WordPress capability required.
     *
     * @return string WordPress capability name.
     */
    public function getRequiredCapability(): string
    {
        return 'moderate_comments';
    }

    /**
     * Executes the ability.
     *
     * @param array<string, mixed> $input Validated input data.
     * @return array<string, mixed> Moderation results.
     */
    public function doExecute(array $input): array
    {
        $comment_ids = array_map('intval', $input['comment_ids']);
        $action = $input['action'];

        $results = array();
        $succeeded = 0;
        $failed = 0;

        foreach ($comment_ids as $comment_id) {
            $result = $this->moderateComment($comment_id, $action);
            $results[] = $result;

            if ($result['success']) {
                $succeeded++;
            } else {
                $failed++;
            }
        }

        return array(
            'action'    => $action,
            'processed' => count($comment_ids),
            'succeeded' => $succeeded,
            'failed'    => $failed,
            'results'   => $results,
        );
    }

    /**
     * Moderate a single comment.
     *
     * @param int    $comment_id Comment ID.
     * @param string $action     Moderation action.
     * @return array<string, mixed> Result array.
     */
    private function moderateComment(int $comment_id, string $action): array
    {
        // Verify comment exists.
        $comment = get_comment($comment_id);
        if (null === $comment) {
            return array(
                'comment_id' => $comment_id,
                'success'    => false,
                'error'      => 'Comment not found',
            );
        }

        $success = false;
        $error = '';

        switch ($action) {
            case 'approve':
                $success = (bool) wp_set_comment_status($comment_id, 'approve');
                break;

            case 'unapprove':
                $success = (bool) wp_set_comment_status($comment_id, 'hold');
                break;

            case 'spam':
                $success = (bool) wp_spam_comment($comment_id);
                break;

            case 'unspam':
                $success = (bool) wp_unspam_comment($comment_id);
                break;

            case 'trash':
                $success = (bool) wp_trash_comment($comment_id);
                break;

            case 'untrash':
                $success = (bool) wp_untrash_comment($comment_id);
                break;

            default:
                $error = 'Invalid action';
        }

        $result = array(
            'comment_id' => $comment_id,
            'success'    => $success,
        );

        if (!$success && '' === $error) {
            $error = 'Operation failed';
        }

        if ('' !== $error) {
            $result['error'] = $error;
        }

        return $result;
    }
}
