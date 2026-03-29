<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Intervention\Image\ImageManagerStatic as Image;

class ProfileController extends Controller
{
public function edit()
{
return Inertia::render('Profile/Edit', [
'user' => auth()->user(),
]);
}

public function update(Request $request)
{
$request->validate([
'name' => 'required|string|max:255',
'avatar' => 'nullable|image|max:2048',
]);

$user = auth()->user();
$user->name = $request->name;

if ($request->hasFile('avatar')) {
$path = $request->file('avatar')->store('avatars', 'public');
$user->avatar = $path;
}

$user->save();

return redirect()->route('profile.edit')->with('success', 'Perfil actualizado correctamente.');
}
}