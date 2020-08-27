<?php

namespace App\Http\Controllers;

use App\Post;
use App\Like;
use Request;
use Illuminate\Support\Facades\Auth;

class PostsController extends Controller
{
    public function _construct()
    {
    }

    public function index()
    {
        $count = 20;

        Request::validate([
            'page' => 'nullable|numeric',
        ]);

        $page = intval(Request::query('page', 1));
        $pageCount = Post::count();

        //Posts
        $posts = Post::with(['likes', 'user'])
        ->orderBy('id', 'desc')
        ->skip($count * ($page - 1))
        ->take($count)
        ->get();

        return view('posts.index', [
            'posts' => $posts,
            'pagination' => [
                'current' => $page,
                'count' => intval(ceil($pageCount / $count)),
            ],
        ]);
    }

    public function liked()
    {
        $count = 20;

        Request::validate([
            'page' => 'nullable|numeric',
        ]);

        $page = intval(Request::query('page', 1));
        $pageCount = Post::whereIn('id', function ($query) {
            return $query->select('post_id')->from('likes')->where('user_id', Auth::id());
        })->count();


        //Posts
        $posts = Post::with(['likes', 'user'])
            ->whereIn('id', function ($query) {
                return $query->select('post_id')->from('likes')->where('user_id', Auth::id());
            })
            ->orderBy('id', 'desc')
            ->skip($count * ($page - 1))
            ->take($count)
            ->get();

        return view('posts.index', [
            'posts' => $posts,
            'pagination' => [
                'current' => $page,
                'count' => intval(ceil($pageCount / $count)),
            ],
        ]);
    }

    public function following()
    {
        $count = 20;

        Request::validate([
            'page' => 'nullable|numeric',
        ]);

        //$_GET ?page=count, default 1
        $page = intval(Request::query('page', 1));
        $pageCount = Post::whereIn('user_id', function ($query) {
            return $query->select('user_2')->from('follows')->where('user_1', Auth::id());
        })->count();

        //Posts
        $posts = Post::with(['likes', 'user'])
        ->whereIn('user_id', function ($query) {
            return $query->select('user_2')->from('follows')->where('user_1', Auth::id());
        })
        ->orderBy('id', 'desc')
        ->skip($count * ($page - 1))
        ->take($count)
        ->get();

        return view('posts.index', [
            'posts' => $posts,
            'pagination' => [
                'current' => $page,
                'count' => intval(ceil($pageCount / $count)),
            ],
        ]);
    }

    public function create()
    {
        return view('posts.create');
    }

    public function store()
    {
        Request::validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'description' => 'required|max:128',
        ]);

        $imageName = time().'.'.request()->image->getClientOriginalExtension();

        request()->image->move(public_path('images').'/posts/', $imageName);

        $post = new Post();

        $post->user_id = Auth::id();
        $post->image = $imageName;
        $post->description = request()->description;

        $post->save();

        return redirect('posts/'.$post->id);
    }

    public function show($id)
    {
        return view('posts.index', [
            'posts' => [Post::findOrFail($id)],
        ]);
    }

    public function edit($id)
    {
        return view('posts.edit', [
            'post' => Post::findOrFail($id),
        ]);
    }

    public function update($id)
    {
        $post = Post::find($id);

        if (intval($post->user_id) === Auth::id()) {
            Request::validate([
                'description' => 'required|max:255',
            ]);

            $post->description = request()->description;

            $post->save();
        }

        return redirect('posts/'.$post->id);
    }

    public function destroy($id)
    {
        $post = Post::find($id);

        if (intval($post->user_id) === Auth::id()) {
            $post->delete();
        }

        return redirect('posts/');
    }

  
    public function like($id)
    {
        $record = Like::where([
            ['user_id', Auth::id()],
            ['post_id', $id],
        ]);

        if (null === $record->first()) {
            $like = new Like();

            $like->user_id = Auth::id();
            $like->post_id = $id;
            $like->save();

        } else {
            $record->delete();
        }

        return response()->json(null, 200);
    }
}
