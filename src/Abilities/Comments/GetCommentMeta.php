<?php

/**
 * GetCommentMeta ability - retrieves metadata for a comment.
 *
 * @package FAWpmcp\Abilities\Comments
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Comments;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Exceptions\CommentNotFoundException;

/**
 * Ability to retrieve metadata for a WordPress comment.
 *
 * Returns specific meta value or all meta if key not specified.
 *
 * @package FAWpmcp\Abilities\Comments
 */
final class GetCommentMeta extends AbstractAbility
{
    /**
     * Returns the ability identifier.
     *
     * @return string Ability name.
     */
    public function getName(): string
    {
        return 'fa-wpmcp/get-comment-meta';
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
        return 'Get Comment Meta';
    }

    /**
     * Returns the ability description.
     *
     * @return string Description.
     */
    public function getDescription(): string
    {
        return 'Retrieve metadata for a WordPress comment. If key is provided, returns that specific meta value. Otherwise returns all meta.';
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
                    'description' => 'Optional. The meta key to retrieve. If omitted, returns all meta.',
                ),
                'single' => array(
                    'type'        => 'boolean',
                    'description' => 'Optional. Whether to return a single value. Default true.',
                    'default'     => true,
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
                'meta_key'   => array('type' => 'string'),
                'meta_value' => array(),
                'meta'       => array(
                    'type' => 'object',
                    'additionalProperties' => true,
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

        // If meta_key provided, get specific meta.
        if (isset($input['meta_key']) && '' !== $input['meta_key']) {
            $single = $input['single'] ?? true;
            $meta_value = get_comment_meta($comment_id, $input['meta_key'], $single);

            return array(
                'comment_id' => $comment_id,
                'meta_key'   => $input['meta_key'],
                'meta_value' => $meta_value,
            );
        }

        // Otherwise get all meta.
        $all_meta = get_comment_meta($comment_id);

        // Flatten single-value arrays for cleaner output.
        $meta = array();
        foreach ($all_meta as $key => $values) {
            $meta[$key] = count($values) === 1 ? $values[0] : $values;
        }

        return array(
            'comment_id' => $comment_id,
            'meta'       => $meta,
        );
    }
}
