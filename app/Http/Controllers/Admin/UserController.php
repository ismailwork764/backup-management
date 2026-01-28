<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function index()
    {
        return view('admin.users');
    }

    public function apiIndex()
    {
        $users = User::query();

        return DataTables::of($users)
            ->addColumn('actions', function($user){
                $deleteBtn = '';
                if ($user->id !== Auth::id()) {
                    $deleteBtn = '<form action="'.route('admin.users.destroy', $user->id).'" method="POST" class="d-inline"
                        onsubmit="return confirm(\'Are you sure you want to delete this user?\');">
                        <input type="hidden" name="_method" value="DELETE">
                        <input type="hidden" name="_token" value="'.csrf_token().'">
                        <button type="submit" class="btn btn-sm btn-danger ml-1">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </form>';
                }
                return $deleteBtn;
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    public function destroy(User $user)
    {
        if ($user->id === Auth::id()) {
            return back()->withErrors(['error' => 'You cannot delete yourself.']);
        }

        $user->delete();

        return back()->with('success', 'User deleted successfully.');
    }
}
