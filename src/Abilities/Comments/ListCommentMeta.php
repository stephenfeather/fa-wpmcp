<?php

/**
 * ListCommentMeta ability - lists all metadata for a comment.
 *
 * @package FAWpmcp\Abilities\Comments
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Comments;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Exceptions\CommentNotFoundException;

/**
 * Ability to list all metadata for a WordPress comment.
 *
 * Returns all meta keys and values associated with the comment.
 *
 * @package FAWpmcp\Abilities\Comments
 */
final class ListCommentMeta extends AbstractAbility
{
    /**
     * Returns the ability identifier.
     *
     * @return string Ability name.
     */
    public function getName(): string
    {
        return 'fa-wpmcp/list-comment-meta';
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
        return 'List Comment Meta';
    }

    /**
     * Returns the ability description.
     *
     * @return string Description.
     */
    public function getDescription(): string
    {
        return 'List all metadata for a WordPress comment. Returns all meta keys and their values.';
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
            ),
            'required'   => array('comment_id'),
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
                'comment_id' => array('type' => 'integer'),
                'meta'       => array(
                    'type'  => 'array',
                    'items' => array(
                        'type'       => 'object',
                        'properties' => array(
                            'key'   => array('type' => 'string'),
                            'value' => array(),
                        ),
                    ),
                ),
                'count' => array('type' => 'integer'),
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
        return 'read';
    }

    /**
     * Executes the ability.
     *
     * @param array<string, mixed> $input Validated input data.
     * @return array<string, mixed> Meta data.
     * @throws CommentNotFoundException If comment not found.
     */
    public function doExecute(array $input): array
    {
        $comment_id = (int) $input['comment_id'];

        // Verify comment exists.
        $comment = get_comment($comment_id);
        if (null === $comment) {
            throw new CommentNotFoundException('Comment not found');
        }

        // Get all meta.
        $all_meta = get_comment_meta($comment_id);

        // Format as array of key-value pairs.
        $meta = array();
        foreach ($all_meta as $key => $values) {
            $meta[] = array(
                'key'   => $key,
                'value' => count($values) === 1 ? $values[0] : $values,
            );
        }

        return array(
            'comment_id' => $comment_id,
            'meta'       => $meta,
            'count'      => count($meta),
        );
    }
}
