<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PostController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['index', 'show']);
    }
    public function index() {
        $posts = Post::with('user')
            ->select('id', 'title', 'content', 'user_id', 'created_at')
            ->latest()
            ->paginate(10);
        
        return response()->json([
            'data' => $posts->map(function($post) {
                return [
                    'id' => $post->id,
                    'title' => $post->title,
                    'excerpt' => substr($post->content, 0, 100),
                    'author' => $post->user->name,
                    'created_at' => $post->created_at->toDateTimeString(),
                ];
            }),
            'total' => $posts->total(),
            'per_page' => $posts->perPage(),
        ]);
    }
    
    public function store(Request $request) {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Unauthorized. Token not provided or invalid.'
            ], Response::HTTP_UNAUTHORIZED);
        }
        try {
            $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $errors = [];
            foreach ($e->errors() as $field => $messages) {
                $errors[$field] = implode(', ', $messages);
            }
            return response()->json(['errors' => $errors], 422);
        }
        //dd(auth()->id());exit;
    

        $post = Post::create([
            'title' => $validatedData['title'],
            'content' => $validatedData['content'],
            'user_id' => auth()->id(),
        ]);
            
        return response()->json($post, 201);
    }
    
    public function show($id) {
        $post = Post::with('user')->findOrFail($id);
        return response()->json($post);
    }
    
    public function update(Request $request, $id) {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Unauthorized. Token not provided or invalid.'
            ], Response::HTTP_UNAUTHORIZED);
        }
        $post = Post::findOrFail($id);
    
        if ($post->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        try {
            $validatedData = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'content' => 'sometimes|required|string',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $errors = [];
            foreach ($e->errors() as $field => $messages) {
            $errors[$field] = implode(', ', $messages);
            }
            return response()->json(['errors' => $errors], 422);
        }
        $post->update($validatedData);
    
        return response()->json($post);
    }
    
    public function destroy($id) {
        $post = Post::findOrFail($id);
    
        if ($post->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
    
        $post->delete();
    
        return response()->json(['message' => 'Post deleted']);
    }
}
