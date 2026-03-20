<h1>Create User</h1>

<form method="POST" action="{{ route('admin.users.store') }}">
    @csrf

    <input type="text" name="name" placeholder="Name"><br>
    <input type="email" name="email" placeholder="Email"><br>
    
    <div>
    <label class="block text-sm font-semibold mb-1">Утасны дугаар</label>
    <input name="phone" value="{{ old('phone') }}"
           class="w-full border rounded-md px-3 py-2"
           placeholder="8 оронтой (ж: 99112233)">
    @error('phone')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
</div>

<div>
    <label class="block text-sm font-semibold mb-1">Регистрийн дугаар</label>
    <input name="register_number" value="{{ old('register_number') }}"
           class="w-full border rounded-md px-3 py-2 uppercase"
           oninput="this.value=this.value.toUpperCase()"
           placeholder="Ж: АБ12345678">
    @error('register_number')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
</div>
    <input type="password" name="password" placeholder="Password"><br>

    <select name="role">
        @foreach($roles as $role)
            <option value="{{ $role->name }}">{{ $role->name }}</option>
        @endforeach
    </select><br>

    <button type="submit">Save</button>
</form>