<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PostController extends Controller
{
    public function getAllPosts()
    {
        $posts = Post::with('author')->withCount('comments')->get();
        return response()->json($posts);
    }

    public function getPost($id)
    {
        $post = Post::with('author')->with('comments')->findOrFail($id);
        return response()->json($post);
    }

    public function likePost($id)
{
    $post = Post::findOrFail($id);

    $post->likes += 1;
    $post->save();

    return response()->json($post);
}

    public function createPost(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'title_content' => 'required|json',
            'content' => 'required|string',
            'url' => 'required|url|max:255',
            'category_id' => 'required|exists:categories,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $post = Post::create([
            'title' => $request->title,
            'title_content' => $request->title_content,
            'content' => $request->content,
            'url' => $request->url,
            'author_id' => Auth::id(),
            'category_id' => $request->category_id,
        ]);

        return response()->json($post, 201);
    }

    public function editPost(Request $request, $id)
    {
        $post = Post::findOrFail($id);

        if ($post->author_id != Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'title_content' => 'sometimes|json',
            'content' => 'sometimes|string',
            'url' => 'sometimes|url|max:255',
            'category_id' => 'sometimes|exists:categories,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $post->update($request->only('title', 'title_content', 'content', 'url', 'category_id'));

        return response()->json($post);
    }

    public function deletePost($id)
    {
        $post = Post::findOrFail($id);

        // Vérifier si l'utilisateur authentifié est l'auteur ou un administrateur
        if ($post->author_id != Auth::id() && !Auth::guard('admin')->check()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $post->delete();

        return response()->json(['message' => 'Post deleted successfully']);
    }
}
