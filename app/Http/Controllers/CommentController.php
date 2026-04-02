<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Application;
use App\Models\Evaluation;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CommentController extends Controller
{
    /**
     * Get comments for a commentable entity
     */
    public function index(Request $request, string $type, int $id)
    {
        try {
            $model = $this->resolveModel($type);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }

        $entity = $model::find($id);

        if (!$entity) {
            return response()->json(['message' => 'Entity not found'], 404);
        }

        $comments = $entity->comments()
            ->with('user', 'parent')
            ->whereNull('parent_id')
            ->with('replies.user')
            ->get();

        return response()->json(['data' => $comments]);
    }

    /**
     * Store a new comment
     */
    public function store(Request $request, string $type, int $id)
    {
        try {
            $model = $this->resolveModel($type);
        } catch (\InvalidArgumentException $e) {
            ActivityLogger::log('comment_create_failed', null, [
                'reason' => $e->getMessage(),
                'type' => $type,
                'entity_id' => $id,
            ]);
            return response()->json(['message' => $e->getMessage()], 400);
        }

        $entity = $model::find($id);

        if (!$entity) {
            ActivityLogger::log('comment_create_failed', null, [
                'reason' => 'Entity not found',
                'type' => $type,
                'entity_id' => $id,
            ]);
            return response()->json(['message' => 'Entity not found'], 404);
        }

        try {
            $validated = $request->validate([
                'content' => 'required|string|max:5000',
                'user_id' => 'required|exists:users,id',
                'parent_id' => 'nullable|exists:comments,id',
            ]);
        } catch (ValidationException $e) {
            ActivityLogger::log('comment_create_failed', null, [
                'validation_errors' => $e->errors(),
                'input' => [
                    'content' => $request->input('content'),
                    'user_id' => $request->input('user_id'),
                    'parent_id' => $request->input('parent_id'),
                ],
                'type' => $type,
                'entity_id' => $id,
            ]);
            throw $e;
        }

        $comment = DB::transaction(function () use ($entity, $validated, $type, $id) {
            // If parent_id is provided, verify it belongs to the same entity
            if (!empty($validated['parent_id'])) {
                $parent = Comment::find($validated['parent_id']);
                if (!$parent || $parent->commentable_type !== get_class($entity) || $parent->commentable_id !== $entity->id) {
                    ActivityLogger::log('comment_create_failed', null, [
                        'reason' => 'Invalid parent comment',
                        'parent_id' => $validated['parent_id'],
                        'type' => $type,
                        'entity_id' => $id,
                    ]);
                    return response()->json([
                        'message' => 'Invalid parent comment',
                    ], 422);
                }
            }

            return $entity->comments()->create([
                'content' => $validated['content'],
                'user_id' => $validated['user_id'] ?? null,
                'parent_id' => $validated['parent_id'] ?? null,
            ]);
        });

        if ($comment instanceof \Illuminate\Http\JsonResponse) {
            return $comment;
        }

        ActivityLogger::created($comment, [
            'type' => $type,
            'entity_id' => $id,
            'parent_id' => $validated['parent_id'] ?? null,
        ]);

        return response()->json([
            'message' => 'Comment created successfully',
            'data' => $comment->load('user', 'parent'),
        ], 201);
    }

    /**
     * Update a comment
     */
    public function update(Request $request, int $id)
    {
        $comment = Comment::find($id);

        if (!$comment) {
            ActivityLogger::log('comment_update_failed', null, [
                'reason' => 'Comment not found',
                'comment_id' => $id,
            ]);
            return response()->json(['message' => 'Comment not found'], 404);
        }

        try {
            $validated = $request->validate([
                'content' => 'required|string|max:5000',
            ]);
        } catch (ValidationException $e) {
            ActivityLogger::log('comment_update_failed', null, [
                'validation_errors' => $e->errors(),
                'comment_id' => $id,
                'input' => [
                    'content' => $request->input('content'),
                ],
            ]);
            throw $e;
        }

        $oldContent = $comment->content;
        $comment->update($validated);

        ActivityLogger::updated($comment, ['content' => $oldContent], ['content' => $validated['content']]);

        return response()->json([
            'message' => 'Comment updated successfully',
            'data' => $comment->fresh('user'),
        ]);
    }

    /**
     * Delete a comment
     */
    public function destroy(int $id)
    {
        $comment = Comment::find($id);

        if (!$comment) {
            ActivityLogger::log('comment_delete_failed', null, [
                'reason' => 'Comment not found',
                'comment_id' => $id,
            ]);
            return response()->json(['message' => 'Comment not found'], 404);
        }

        $deletedComment = $comment->fresh();
        $comment->delete();

        ActivityLogger::deleted($deletedComment, [
            'content' => $deletedComment->content,
            'commentable_type' => $deletedComment->commentable_type,
        ]);

        return response()->json(['message' => 'Comment deleted successfully']);
    }

    /**
     * Resolve model class from type string
     */
    private function resolveModel(string $type): string
    {
        return match ($type) {
            'applications' => Application::class,
            'evaluations' => Evaluation::class,
            default => throw new \InvalidArgumentException("Invalid type: {$type}"),
        };
    }
}
