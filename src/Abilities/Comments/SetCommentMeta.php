<?php

/**
 * SetCommentMeta ability - sets metadata for a comment.
 *
 * @package FAWpmcp\Abilities\Comments
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Comments;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Exceptions\CommentNotFoundException;

/**
 * Ability to set metadata for a WordPress comment.
 *
 * Creates or updates the specified meta key with the given value.
 *
 * @package FAWpmcp\Abilities\Comments
 */
final class SetCommentMeta extends AbstractAbility
{
    /**
     * Returns the ability identifier.
     *
     * @return string Ability name.
     */
    public function getName(): string
    {
        return 'fa-wpmcp/set-comment-meta';
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
        return 'Set Comment Meta';
    }

    /**
     * Returns the ability description.
     *
     * @return string Description.
     */
    public function getDescription(): string
    {
        return 'Set or update metadata for a WordPress comment. Creates the meta key if it does not exist.';
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
                    'description' => 'The meta key to set.',
                    'minLength'   => 1,
                ),
                'meta_value' => array(
                    'description' => 'The value to set. Can be string, number, boolean, array, or object.',
                ),
            ),
            'required'   => array('comment_id', 'meta_key', 'meta_value'),
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
                'meta_id'    => array('type' => 'integer'),
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
        $meta_value = $input['meta_value'];

        // Verify comment exists.
        $comment = get_comment($comment_id);
        if (null === $comment) {
            throw new CommentNotFoundException('Comment not found');
        }

        // Update or add the meta.
        $result = update_comment_meta($comment_id, $meta_key, $meta_value);

        // update_comment_meta returns meta_id on add, true on update, false on failure.
        $success = false !== $result;
        $meta_id = is_int($result) ? $result : 0;

        return array(
            'success'    => $success,
            'comment_id' => $comment_id,
            'meta_key'   => $meta_key,
            'meta_id'    => $meta_id,
        );
    }
}
