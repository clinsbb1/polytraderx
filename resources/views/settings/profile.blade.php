@extends('layouts.admin')

@section('title', 'Profile Settings')

@section('content')
<h4 class="mb-4" style="font-family: var(--font-display);">Profile Settings</h4>

<div class="ptx-card" style="max-width:600px">
    <div class="ptx-card-body">
        <form method="POST" action="/settings/profile">
            @csrf

            <div class="mb-3">
                <label class="ptx-label">Name</label>
                <input type="text" name="name" class="ptx-input" value="{{ old('name', $user->name) }}" required>
                @error('name') <div class="ptx-input-error">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label class="ptx-label">Email</label>
                <input type="email" name="email" class="ptx-input" value="{{ old('email', $user->email) }}" required>
                @error('email') <div class="ptx-input-error">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label class="ptx-label">Timezone</label>
                <select name="timezone" class="ptx-input">
                    @foreach(timezone_identifiers_list() as $tz)
                        <option value="{{ $tz }}" {{ ($user->timezone ?? 'Africa/Lagos') === $tz ? 'selected' : '' }}>{{ $tz }}</option>
                    @endforeach
                </select>
                @error('timezone') <div class="ptx-input-error">{{ $message }}</div> @enderror
            </div>

            <hr>

            <div class="mb-3">
                <label class="ptx-label">New Password</label>
                <input type="password" name="password" class="ptx-input" placeholder="Leave blank to keep current">
                @error('password') <div class="ptx-input-error">{{ $message }}</div> @enderror
            </div>

            <div class="mb-4">
                <label class="ptx-label">Confirm Password</label>
                <input type="password" name="password_confirmation" class="ptx-input">
            </div>

            <button type="submit" class="btn-ptx-primary btn-ptx-sm">Save Profile</button>
        </form>
    </div>
</div>
@endsection
