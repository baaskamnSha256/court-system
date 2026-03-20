<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Illuminate\Validation\Rule;  

class UsersController extends Controller
{
    public function index(Request $request)
{
    $q = trim((string)$request->get('q', ''));

    // role filter (roles.name)
    $role = $request->get('role'); // string|null

    // active filter: all | active | inactive
    $status = $request->get('status', 'all');

    $users = User::query()
        // ✅ Search
        ->when($q !== '', function ($query) use ($q) {
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                  ->orWhere('email', 'like', "%{$q}%")
                  ->orWhere('phone', 'like', "%{$q}%")
                  ->orWhere('register_number', 'like', "%{$q}%")
                  ->orWhere('workplace', 'like', "%{$q}%");
            });
        })

        // ✅ Role filter (spatie)
        ->when(!empty($role) && $role !== 'all', function ($query) use ($role) {
            $query->role($role);
        })

        // ✅ Active filter
        ->when($status === 'active', fn($query) => $query->where('is_active', true))
        ->when($status === 'inactive', fn($query) => $query->where('is_active', false))

        ->orderByDesc('id')
        ->paginate(15)
        ->appends([
            'q' => $q,
            'role' => $role,
            'status' => $status,
        ]);

    $roles = Role::orderBy('name')->get();

    // ✅ role бүрийн count (filter-үүдээс ХАМААРУУЛАХ эсэхийг чи шийднэ)
    // Доорх нь НИЙТ-ийг (filter үл хамаарна) тоолж байна:
    $roleCounts = User::query()
        ->selectRaw('roles.name as role, COUNT(*) as total')
        ->join('model_has_roles', 'model_has_roles.model_id', '=', 'users.id')
        ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
        ->where('model_has_roles.model_type', User::class)
        ->groupBy('roles.name')
        ->pluck('total', 'role')
        ->toArray();

    $totalUsers = User::count();

    return view('admin.users.index', compact(
        'users','roles','roleCounts','totalUsers','q','role','status'
    ));
}

    public function store(Request $request)
    {
        
            $request->merge([
                'register_number' => $request->register_number
                    ? mb_strtoupper(trim($request->register_number), 'UTF-8')
                    : null,
            ]);
        
            $data = $request->validate([
                'name' => ['required','string','max:255'],
                'email' => ['required','email','unique:users,email'],
                'password' => ['required','min:6'],
        
                'phone' => ['nullable','regex:/^[0-9]{8}$/'],
        
                'register_number' => [
                    'nullable',
                    'regex:/^[А-ЯӨҮЁ]{2}[0-9]{8}$/u',
                    'unique:users,register_number',
                ],
        
                'workplace' => ['nullable','string','max:255'],
                'role' => ['required','string'],
                'is_active' => ['nullable','boolean'],
            ]);
        
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'phone' => $data['phone'] ?? null,
                'register_number' => $data['register_number'] ?? null,
                'workplace' => $data['workplace'] ?? null,
                'is_active' => $request->boolean('is_active', true),
            ]);
        
            $user->assignRole($data['role']);
        
            return back()->with('success', 'Хэрэглэгч нэмлээ.');
        
    }

    public function toggle(User $user)
    {
        $user->is_active = ! $user->is_active;
        $user->save();

        return back()->with('success','Идэвх шинэчлэгдлээ.');
    }

    public function edit(User $user)
    {
    $roles = Role::orderBy('name')->get();
    return view('admin.users.edit', compact('user','roles'));
    }

    public function update(Request $request, User $user)
    {
        $request->merge([
            'register_number' => $request->register_number
                ? mb_strtoupper(trim($request->register_number), 'UTF-8')
                : null,
        ]);

    $data = $request->validate([
        'name' => ['required','string','max:255'],
        'email' => ['required','email','max:255','unique:users,email,'.$user->id],
        'workplace' => ['nullable','string','max:255'],
        'role' => ['required','string'],
        'password' => ['nullable','string','min:6'],
        'phone' => ['nullable','regex:/^[0-9]{8}$/'],
        'register_number' => [
        'nullable',
        'regex:/^[А-ЯӨҮЁ]{2}[0-9]{8}$/u',
        Rule::unique('users','register_number')->ignore($user->id),
        ],
    ]);

    $user->name = $data['name'];
    $user->email = $data['email'];
    $user->workplace = $data['workplace'] ?? null;
    $user->phone = $data['phone'] ?? null;
    $user->register_number = $data['register_number'] ?? null;

    if (!empty($data['password'])) {
        $user->password = Hash::make($data['password']);
    }

    $user->save();

    $user->syncRoles([$data['role']]);

    return redirect()->route('admin.users.index')->with('success','Хэрэглэгчийн мэдээлэл шинэчлэгдлээ.');
}

}
