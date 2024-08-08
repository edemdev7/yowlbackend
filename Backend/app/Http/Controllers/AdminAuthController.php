<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Post;
use App\Models\Comment;

class AdminAuthController extends Controller
{
    // Inscription admin
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|max:255|unique:admins',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $admin = Admin::create([
            'username' => $request->username,
            'password' => Hash::make($request->password),
        ]);

        $token = $admin->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    // Connexion admin
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $admin = Admin::where('username', $request->username)->first();

        if (!$admin || !Hash::check($request->password, $admin->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $token = $admin->createToken('admin_token')->plainTextToken;

        return response()->json(['token' => $token], 200);
    }
    public function logout(Request $request)
    {
        // Vérifiez si l'utilisateur est authentifié
        if (!$request->user()) {
            return response()->json(['message' => 'No authenticated user found'], 401);
        }

        // Vérifiez si l'utilisateur a des tokens
        $user = $request->user();
        $token = $user->currentAccessToken();

        if (!$token) {
            return response()->json(['message' => 'No active session found'], 404);
        }

        // Supprimer le token
        $token->delete();

        return response()->json(['message' => 'Logged out successfully'], 200);
    }

    // Afficher le profil de l'admin
    public function profile(Request $request)
    {
        return response()->json($request->user());
    }

    // Mise à jour du profil de l'admin
    public function updateProfile(Request $request)
    {
        $admin = Auth::user();

        $validator = Validator::make($request->all(), [
            'username' => 'sometimes|string|max:255|unique:admins,username,'.$admin->id,
            'password' => 'sometimes|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        if ($request->has('username')) {
            $admin->username = $request->username;
        }

        if ($request->has('password')) {
            $admin->password = Hash::make($request->password);
        }

        $admin->save();

        return response()->json(['message' => 'Profile updated successfully']);
    }

   
    public function getKPI()
    {
        // Number of users
        $numberOfUsers = User::count();

        // Number of posts
        $numberOfPosts = Post::count();

        // Most commented post
        $mostCommentedPost = Post::withCount('comments')
            ->orderBy('comments_count', 'desc')
            ->first();

        // Most liked comment
        $mostLikedComment = Comment::orderBy('likes', 'desc')
            ->first();

        // User age statistics
        $userAgeStat = User::select(DB::raw('YEAR(CURDATE()) - YEAR(date_of_birth) as age'), DB::raw('COUNT(*) as count'))
            ->groupBy('age')
            ->get();

        return response()->json([
            'numberOfUsers' => $numberOfUsers,
            'numberOfPosts' => $numberOfPosts,
            'mostCommentedPost' => $mostCommentedPost,
            'mostLikedComment' => $mostLikedComment,
            'userAgeStat' => $userAgeStat,
        ]);

    }
}