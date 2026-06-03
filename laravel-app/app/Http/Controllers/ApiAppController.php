<?php

namespace App\Http\Controllers;

use App\Models\Note;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ApiAppController extends Controller
{
    public function handle(string $action, Request $request)
    {
        $user = $this->authUser();

        switch ($action) {
            case 'me':
                return response()->json(['user' => $user ? $this->serializeUser($user) : null]);

            case 'login':
                $email = $request->input('email');
                $password = $request->input('pass');
                $u = User::where('email', $email)->first();
                if (!$u || !Hash::check($password, $u->password)) {
                    return response()->json(['error' => 'Wrong email or password.']);
                }
                session(['user_id' => $u->id]);
                return response()->json(['user' => $this->serializeUser($u)]);

            case 'register':
                $existing = User::where('email', $request->input('email'))->exists();
                if ($existing) {
                    return response()->json(['error' => 'Email already exists.']);
                }
                $u = User::create([
                    'name' => $request->input('name'),
                    'email' => $request->input('email'),
                    'password' => Hash::make($request->input('pass')),
                    'role' => 'User',
                ]);
                return response()->json(['user' => $this->serializeUser($u)]);

            case 'logout':
                session()->forget('user_id');
                return response()->json(['ok' => true]);

            case 'notes':
                if (!$user) return response()->json(['error' => 'Unauthorized'], 401);
                return response()->json(['notes' => Note::where('user_id', $user->id)->orderByDesc('id')->get()]);

            case 'users':
                if (!$user || $user->role !== 'Admin') return response()->json(['error' => 'Admins only.'], 403);
                return response()->json(['users' => User::orderByDesc('id')->get()->map(fn($u) => $this->serializeUser($u))]);

            case 'save_note':
                if (!$user) return response()->json(['error' => 'Unauthorized'], 401);
                $note = $request->input('id') ? Note::find($request->input('id')) : new Note();
                if ($note && $note->user_id !== $user->id) return response()->json(['error' => 'Forbidden'], 403);
                $note->user_id = $user->id;
                $note->title = $request->input('title');
                $note->body = $request->input('body');
                $note->cat = $request->input('cat', 'Personal');
                $note->clr = $request->input('clr', 'cb');
                $note->save();
                return response()->json(['ok' => true, 'note' => $note]);

            case 'delete_note':
                if (!$user) return response()->json(['error' => 'Unauthorized'], 401);
                $note = Note::find($request->input('id'));
                if (!$note || $note->user_id !== $user->id) return response()->json(['error' => 'Forbidden'], 403);
                $note->delete();
                return response()->json(['ok' => true]);

            case 'save_user':
                if (!$user || $user->role !== 'Admin') return response()->json(['error' => 'Admins only.'], 403);
                $u = $request->input('id') ? User::find($request->input('id')) : new User();
                if (!$u) return response()->json(['error' => 'User not found.'], 404);
                $u->name = $request->input('name');
                $u->email = $request->input('email');
                $u->role = $request->input('role', 'User');
                if ($request->filled('pass')) $u->password = Hash::make($request->input('pass'));
                $u->save();
                return response()->json(['ok' => true, 'user' => $this->serializeUser($u)]);

            case 'delete_user':
                if (!$user || $user->role !== 'Admin') return response()->json(['error' => 'Admins only.'], 403);
                $u = User::find($request->input('id'));
                if (!$u) return response()->json(['error' => 'User not found.'], 404);
                $u->delete();
                return response()->json(['ok' => true]);

            case 'save_profile':
                if (!$user) return response()->json(['error' => 'Unauthorized'], 401);
                $user->name = $request->input('name', $user->name);
                $user->email = $request->input('email', $user->email);
                $user->addr = $request->input('addr', $user->addr);
                $user->gender = $request->input('gender', $user->gender);
                if ($request->filled('pass')) $user->password = Hash::make($request->input('pass'));
                if ($request->filled('pic')) $user->pic = $request->input('pic');
                $user->save();
                session(['user_id' => $user->id]);
                return response()->json(['user' => $this->serializeUser($user)]);

            default:
                return response()->json(['error' => 'Unknown action.'], 404);
        }
    }

    private function authUser()
    {
        $id = session('user_id');
        return $id ? User::find($id) : null;
    }

    private function serializeUser(User $u): array
    {
        return [
            'id' => $u->id,
            'name' => $u->name,
            'email' => $u->email,
            'role' => $u->role,
            'addr' => $u->addr,
            'gender' => $u->gender,
            'pic' => $u->pic,
            'created' => $u->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
