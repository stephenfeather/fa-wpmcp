<?php

/**
 * GetCommentReplies ability - gets replies to a comment.
 *
 * @package FAWpmcp\Abilities\Comments
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Comments;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Exceptions\CommentNotFoundException;

/**
 * Ability to get replies to a WordPress comment.
 *
 * Returns all child comments (direct replies) to a parent comment.
 *
 * @package FAWpmcp\Abilities\Comments
 */
final class GetCommentReplies extends AbstractAbility
{
    /**
     * Returns the ability identifier.
     *
     * @return string Ability name.
     */
    public function getName(): string
    {
        return 'fa-wpmcp/get-comment-replies';
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
        return 'Get Comment Replies';
    }

    /**
     * Returns the ability description.
     *
     * @return string Description.
     */
    public function getDescription(): string
    {
        return 'Get replies to a WordPress comment. Returns child comments with optional depth control.';
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
                    'description' => 'The ID of the parent comment.',
                    'minimum'     => 1,
                ),
                'hierarchical' => array(
                    'type'        => 'boolean',
                    'description' => 'Optional. If true, includes nested replies. Default false (direct replies only).',
                    'default'     => false,
                ),
                'status' => array(
                    'type'        => 'string',
                    'description' => 'Optional. Filter by status: approve, hold, spam, trash, all. Default approve.',
                    'enum'        => array('approve', 'hold', 'spam', 'trash', 'all'),
                    'default'     => 'approve',
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
                'parent_id' => array('type' => 'integer'),
                'replies'   => array(
                    'type'  => 'array',
                    'items' => array(
                        'type'       => 'object',
                        'properties' => array(
                            'id'        => array('type' => 'integer'),
                            'parent_id' => array('type' => 'integer'),
                            'post_id'   => array('type' => 'integer'),
                            'author'    => array('type' => 'string'),
                            'email'     => array('type' => 'string'),
                            'content'   => array('type' => 'string'),
                            'date'      => array('type' => 'string'),
                            'status'    => array('type' => 'string'),
                            'replies'   => array('type' => 'array'),
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
     * @return array<string, mixed> Replies data.
     * @throws CommentNotFoundException If parent comment not found.
     */
    public function doExecute(array $input): array
    {
        $comment_id = (int) $input['comment_id'];
        $hierarchical = $input['hierarchical'] ?? false;
        $status = $input['status'] ?? 'approve';

        // Verify parent comment exists.
        $comment = get_comment($comment_id);
        if (null === $comment) {
            throw new CommentNotFoundException('Parent comment not found');
        }

        // Build query args.
        $args = array(
            'parent'  => $comment_id,
            'status'  => $status,
            'orderby' => 'comment_date',
            'order'   => 'ASC',
        );

        // Get direct replies.
        $replies = get_comments($args);

        // Format replies.
        $formatted_replies = array();
        foreach ($replies as $reply) {
            $formatted = $this->formatComment($reply);

            // If hierarchical, get nested replies recursively.
            if ($hierarchical) {
                $nested_input = array(
                    'comment_id'   => (int) $reply->comment_ID,
                    'hierarchical' => true,
                    'status'       => $status,
                );
                $nested_result = $this->doExecute($nested_input);
                $formatted['replies'] = $nested_result['replies'];
            }

            $formatted_replies[] = $formatted;
        }

        return array(
            'parent_id' => $comment_id,
            'replies'   => $formatted_replies,
            'count'     => count($formatted_replies),
        );
    }

    /**
     * Format a WP_Comment object into output array.
     *
     * @param \WP_Comment|object $comment The comment object.
     * @return array<string, mixed> Formatted comment data.
     */
    private function formatComment(object $comment): array
    {
        return array(
            'id'        => (int) $comment->comment_ID,
            'parent_id' => (int) $comment->comment_parent,
            'post_id'   => (int) $comment->comment_post_ID,
            'author'    => $comment->comment_author,
            'email'     => $comment->comment_author_email,
            'content'   => $comment->comment_content,
            'date'      => $comment->comment_date,
            'status'    => $comment->comment_approved,
        );
    }
}
