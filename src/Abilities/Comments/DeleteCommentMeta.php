<?php

/**
 * DeleteCommentMeta ability - deletes metadata from a comment.
 *
 * @package FAWpmcp\Abilities\Comments
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Comments;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Exceptions\CommentNotFoundException;

/**
 * Ability to delete metadata from a WordPress comment.
 *
 * Removes the specified meta key (and optionally a specific value).
 *
 * @package FAWpmcp\Abilities\Comments
 */
final class DeleteCommentMeta extends AbstractAbility
{
    /**
     * Returns the ability identifier.
     *
     * @return string Ability name.
     */
    public function getName(): string
    {
        return 'fa-wpmcp/delete-comment-meta';
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
        return 'Delete Comment Meta';
    }

    /**
     * Returns the ability description.
     *
     * @return string Description.
     */
    public function getDescription(): string
    {
        return 'Delete metadata from a WordPress comment. Optionally specify a value to delete only matching entries.';
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
                'comment_id' => array(
                    'type'        => 'integer',
                    'description' => 'The ID of the comment.',
                    'minimum'     => 1,
                ),
                'meta_key' => array(
                    'type'        => 'string',
                    'description' => 'The meta key to delete.',
                    'minLength'   => 1,
                ),
                'meta_value' => array(
                    'description' => 'Optional. Only delete entries with this specific value.',
                ),
            ),
            'required'   => array('comment_id', 'meta_key'),
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
                'success'    => array('type' => 'boolean'),
                'comment_id' => array('type' => 'integer'),
                'meta_key'   => array('type' => 'string'),
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
     * @return array<string, mixed> Result data.
     * @throws CommentNotFoundException If comment not found.
     */
    public function doExecute(array $input): array
    {
        $comment_id = (int) $input['comment_id'];
        $meta_key = $input['meta_key'];
        $meta_value = $input['meta_value'] ?? '';

        // Verify comment exists.
        $comment = get_comment($comment_id);
        if (null === $comment) {
            throw new CommentNotFoundException('Comment not found');
        }

        // Delete the meta.
        $success = delete_comment_meta($comment_id, $meta_key, $meta_value);

        return array(
            'success'    => $success,
            'comment_id' => $comment_id,
            'meta_key'   => $meta_key,
        );
    }
}
