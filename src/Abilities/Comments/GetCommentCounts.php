<?php

/**
 * GetCommentCounts ability - gets comment counts by status.
 *
 * @package FAWpmcp\Abilities\Comments
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Comments;

use FAWpmcp\Abilities\AbstractAbility;

/**
 * Ability to get WordPress comment counts by status.
 *
 * Returns counts for approved, pending, spam, trash, and total comments.
 * Optionally filter by post ID.
 *
 * @package FAWpmcp\Abilities\Comments
 */
final class GetCommentCounts extends AbstractAbility
{
    /**
     * Returns the ability identifier.
     *
     * @return string Ability name.
     */
    public function getName(): string
    {
        return 'fa-wpmcp/get-comment-counts';
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
        return 'Get Comment Counts';
    }

    /**
     * Returns the ability description.
     *
     * @return string Description.
     */
    public function getDescription(): string
    {
        return 'Get comment counts by status (approved, pending, spam, trash, total). Optionally filter by post ID.';
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
                'post_id' => array(
                    'type'        => 'integer',
                    'description' => 'Optional. Filter counts by post ID. If omitted, returns global counts.',
                    'minimum'     => 1,
                ),
            ),
            'required'   => array(),
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
                'post_id'        => array('type' => 'integer'),
                'approved'       => array('type' => 'integer'),
                'awaiting_moderation' => array('type' => 'integer'),
                'spam'           => array('type' => 'integer'),
                'trash'          => array('type' => 'integer'),
                'post_trashed'   => array('type' => 'integer'),
                'total_comments' => array('type' => 'integer'),
                'all'            => array('type' => 'integer'),
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
     * @return array<string, mixed> Comment counts.
     */
    public function doExecute(array $input): array
    {
        $post_id = isset($input['post_id']) ? (int) $input['post_id'] : 0;

        // Get comment counts from WordPress.
        $counts = wp_count_comments($post_id);

        // Build result array.
        $result = array(
            'approved'            => (int) $counts->approved,
            'awaiting_moderation' => (int) $counts->moderated,
            'spam'                => (int) $counts->spam,
            'trash'               => (int) $counts->trash,
            'post_trashed'        => (int) ($counts->{'post-trashed'} ?? 0),
            'total_comments'      => (int) $counts->total_comments,
            'all'                 => (int) $counts->all,
        );

        // Include post_id if filtered.
        if ($post_id > 0) {
            $result['post_id'] = $post_id;
        }

        return $result;
    }
}
