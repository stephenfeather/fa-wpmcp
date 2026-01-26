<?php

/**
 * UpdateComment ability - updates comment status (moderation).
 *
 * @package FAWpmcp\Abilities\Comments
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Comments;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Exceptions\CommentUpdateException;

/**
 * Ability to update WordPress comment status.
 *
 * Supports comment moderation actions:
 * - approve: Approve the comment
 * - hold: Hold for moderation
 * - spam: Mark as spam
 * - trash: Move to trash
 *
 * @package FAWpmcp\Abilities\Comments
 */
final class UpdateComment extends AbstractAbility
{
    /**
     * Get the unique ability name.
     *
     * @return string Ability name.
     */
    public function getName(): string
    {
        return 'fa-wpmcp/update-comment';
    }

    /**
     * Get the ability category.
     *
     * @return string Category name.
     */
    public function getCategory(): string
    {
        return 'comments';
    }

    /**
     * Get the human-readable label.
     *
     * @return string Ability label.
     */
    public function getLabel(): string
    {
        return 'Update Comment';
    }

    /**
     * Get the ability description.
     *
     * @return string Description.
     */
    public function getDescription(): string
    {
        return 'Update WordPress comment status for moderation. Status options: approve, hold, spam, trash. WARNING: Setting status to "trash" will move the comment to trash.';
    }

    /**
     * Get the input schema.
     *
     * @return array<string, mixed> JSON Schema array.
     */
    public function getInputSchema(): array
    {
        return array(
            'type'       => 'object',
            'properties' => array(
                'comment_id' => array(
                    'type'        => 'integer',
                    'description' => 'The ID of the comment to update.',
                    'minimum'     => 1,
                ),
                'status'     => array(
                    'type'        => 'string',
                    'description' => 'The new comment status.',
                    'enum'        => array( 'approve', 'hold', 'spam', 'trash' ),
                ),
            ),
            'required'   => array( 'comment_id', 'status' ),
        );
    }

    /**
     * Get the output schema.
     *
     * @return array<string, mixed> JSON Schema array.
     */
    public function getOutputSchema(): array
    {
        return array(
            'type'       => 'object',
            'properties' => array(
                'comment_id' => array(
                    'type'        => 'integer',
                    'description' => 'The ID of the updated comment.',
                ),
                'status'     => array(
                    'type'        => 'string',
                    'description' => 'The new comment status.',
                ),
                'link'       => array(
                    'type'        => 'string',
                    'description' => 'Permalink to the comment.',
                ),
            ),
        );
    }

    /**
     * Get the required WordPress capability.
     *
     * @return string WordPress capability name.
     */
    public function getRequiredCapability(): string
    {
        return 'moderate_comments';
    }

    /**
     * Get the operation type.
     *
     * @return string Operation type ('read' or 'write').
     */
    public function getOperationType(): string
    {
        return 'write';
    }

    /**
     * Get ability annotations.
     *
     * Update operations that can trash content are marked as destructive.
     *
     * @return array<string, mixed> Annotations array.
     */
    public function getAnnotations(): array
    {
        $annotations                = parent::getAnnotations();
        $annotations['destructive'] = true;
        return $annotations;
    }

    /**
     * Execute the ability.
     *
     * @param array<string, mixed> $input Validated input data.
     * @return array<string, mixed> Updated comment data.
     * @throws CommentUpdateException If comment update fails.
     */
    public function doExecute(array $input): array
    {
        $comment_id = (int) $input['comment_id'];
        $status     = (string) $input['status'];

        // Update comment status.
        $result = wp_set_comment_status($comment_id, $status);

        if (false === $result) {
            throw new CommentUpdateException('Failed to update comment status');
        }

        return array(
            'comment_id' => $comment_id,
            'status'     => $status,
            'link'       => get_comment_link($comment_id),
        );
    }
}
