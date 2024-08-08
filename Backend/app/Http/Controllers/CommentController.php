<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{

    public function getAllComments()
    {
        $comments = Comment::with('author')->withCount('replies')->get();
        return response()->json($comments);
    }
    
    public function getComment($id)
    {
        $comment = Comment::findOrFail($id);
        return response()->json(['comment' => $comment]);
    }

    public function addComment(Request $request)
    {
        $request->validate([
            'content' => 'required|string',
            'post_id' => 'required|exists:posts,id',
        ]);

        $comment = Comment::create([
            'content' => $request->content,
            'post_id' => $request->post_id,
            'user_id' => Auth::id(),
        ]);

        return response()->json(['comment' => $comment], 201);
    }

    public function editComment(Request $request, $id)
    {
        $comment = Comment::findOrFail($id);

        $this->authorize('update', $comment);

        $request->validate([
            'content' => 'sometimes|required|string',
        ]);

        $comment->update($request->only('content'));

        return response()->json(['comment' => $comment]);
    }

    public function deleteComment($id)
    {
        $comment = Comment::findOrFail($id);

        $this->authorize('delete', $comment);

        $comment->delete();

        return response()->json(['message' => 'Comment deleted successfully']);
    }

    public function replyToComment(Request $request)
    {
        $request->validate([
            'content' => 'required|string',
            'post_id' => 'required|exists:posts,id',
            'parent_id' => 'required|exists:comments,id',
        ]);

        $reply = Comment::create([
            'content' => $request->content,
            'post_id' => $request->post_id,
            'user_id' => Auth::id(),
            'parent_id' => $request->parent_id,
        ]);

        return response()->json(['reply' => $reply], 201);
    }

    public function likeComment($id)
    {
        $comment = Comment::findOrFail($id);
        $comment->increment('likes');

        return response()->json(['message' => 'Comment liked successfully', 'likes' => $comment->likes], 201);
    }

}
