<?php

namespace App\Http\Controllers;

use App\User;
use App\Follow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UsersController extends Controller
{
    public function settings()
    {
        return view('users.account', [
            'user' => User::findOrFail(Auth::id()),
        ]);
    }

    public function show($id)
    {
        return view('users.user', [
            'user' => User::with(['posts', 'posts.likes'])->find($id),
            'followed' => Follow::where([
                ['user_1', Auth::id()],
                ['user_2', $id],
            ])->exists(),
        ]);
    }

    public function update(Request $request)
    {
        $user = User::find(Auth::id());

        $request->validate([
            'image' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'display_name' => 'nullable|string|max:32',
            'biography' => 'nullable|string|max:128',
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:6',
            'new_password' => 'nullable|string|min:6|different:password',
            ]);

        if (null !== $request->image) {
            $imageName = time().'.'.$request->image->getClientOriginalExtension();

            $request->image->move(public_path('images').'/avatar/', $imageName);

            $user->image = $imageName;
        }

        strlen($request->display_name) > 0 ? $user->display_name = $request->display_name : '';
        strlen($request->biography) > 0 ? $user->biography = $request->biography : '';
        strlen($request->new_password) > 0 ? $user->password = Hash::make($request->new_password) : '';

        $user->save();

        return redirect('user/'.Auth::id());
    }

    public function follow($id)
    {
        $record = Follow::where([
            ['user_1', Auth::id()],
            ['user_2', $id],
        ]);

        if (null === $record->first()) {
            $follow = new Follow();

            $follow->user_1 = Auth::id();
            $follow->user_2 = $id;
            $follow->save();

        } else {
            $record->delete();
        }

        return redirect()->route('account.show', ['id' => $id]);
    }
}
