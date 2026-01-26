<?php

/**
 * DeletePost ability - deletes or trashes a WordPress post.
 *
 * @package FAWpmcp\Abilities\Posts
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Posts;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Exceptions\PostDeletionException;
use FAWpmcp\Exceptions\PostNotFoundException;

/**
 * Ability to delete a WordPress post.
 *
 * By default, posts are moved to trash (recoverable).
 * Use force=true to permanently delete.
 *
 * @package FAWpmcp\Abilities\Posts
 */
final class DeletePost extends AbstractAbility
{
    /**
     * Get the unique ability name.
     *
     * @return string Ability name.
     */
    public function getName(): string
    {
        return 'fa-wpmcp/delete-post';
    }

    /**
     * Get the ability category.
     *
     * @return string Category name.
     */
    public function getCategory(): string
    {
        return 'posts-pages';
    }

    /**
     * Get the human-readable label.
     *
     * @return string Ability label.
     */
    public function getLabel(): string
    {
        return 'Delete Post';
    }

    /**
     * Get the ability description.
     *
     * @return string Description.
     */
    public function getDescription(): string
    {
        return 'Delete a WordPress post. By default, moves to trash (recoverable). Set force=true to permanently delete.';
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
                'post_id' => array(
                    'type'        => 'integer',
                    'description' => 'The ID of the post to delete.',
                    'minimum'     => 1,
                ),
                'force'   => array(
                    'type'        => 'boolean',
                    'description' => 'If true, permanently delete instead of trashing. Default: false.',
                    'default'     => false,
                ),
            ),
            'required'   => array( 'post_id' ),
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
                'post_id' => array(
                    'type'        => 'integer',
                    'description' => 'The ID of the deleted post.',
                ),
                'action'  => array(
                    'type'        => 'string',
                    'description' => 'The action performed: "trashed" or "deleted".',
                    'enum'        => array( 'trashed', 'deleted' ),
                ),
                'success' => array(
                    'type'        => 'boolean',
                    'description' => 'Whether the operation succeeded.',
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
        return 'delete_posts';
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
     * Marks this ability as destructive and non-idempotent.
     *
     * @return array<string, mixed> Annotations array.
     */
    public function getAnnotations(): array
    {
        $annotations                = parent::getAnnotations();
        $annotations['destructive'] = true;
        $annotations['idempotent']  = false;
        return $annotations;
    }

    /**
     * Execute the ability.
     *
     * @param array<string, mixed> $input Validated input data.
     * @return array<string, mixed> Deletion result.
     * @throws PostNotFoundException If post does not exist.
     * @throws PostDeletionException If deletion fails.
     */
    public function doExecute(array $input): array
    {
        $post_id = (int) $input['post_id'];
        $force   = (bool) ( $input['force'] ?? false );

        // Verify post exists.
        $post = get_post($post_id);
        if (null === $post) {
            throw new PostNotFoundException("Post {$post_id} not found.");
        }

        if ($force) {
            // Permanent deletion.
            $result = wp_delete_post($post_id, true);
            $action = 'deleted';
        } else {
            // Move to trash (recoverable).
            $result = wp_trash_post($post_id);
            $action = 'trashed';
        }

        if (false === $result || null === $result) {
            throw new PostDeletionException("Failed to {$action} post {$post_id}.");
        }

        return array(
            'post_id' => $post_id,
            'action'  => $action,
            'success' => true,
        );
    }
}
