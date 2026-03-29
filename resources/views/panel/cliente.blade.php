<form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <div>
        <label for="name">Name:</label>
        <input type="text" id="name" name="name" value="{{ auth()->user()->name }}" required>
    </div>
    <div>
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" value="{{ auth()->user()->email }}" required>
    </div>
    <div>
        <label for="avatar">Avatar:</label>
        <input type="file" id="avatar" name="avatar" accept="image/jpeg,image/png,image/webp">
    </div>
    <button type="submit">Update Profile</button>
</form>
