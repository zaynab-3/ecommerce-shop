@extends('layouts.admin')
@section('title', 'Team & access')
@section('content')<x-admin-heading title="Good people, great teamwork"
        description="The right access for every member of your team." eyebrow="TEAM & ACCESS" />
    <div class="admin-form-layout">
        <div class="admin-form-primary">
            @foreach ($admins as $admin)
                <form method="post" action="{{ route('admin.team.update', $admin) }}" class="admin-panel form-panel team-card">
                    @csrf @method('PATCH')<div class="team-profile"><span
                            class="avatar">{{ strtoupper(mb_substr($admin->name, 0, 1)) }}</span>
                        <div>
                            <h2>{{ $admin->name }}{{ $admin->id === auth()->id() ? ' (you)' : '' }}</h2>
                            <p>{{ $admin->email }}</p>
                        </div><span
                            class="status-badge {{ $admin->is_active ? 'status-delivered' : 'status-cancelled' }}">{{ $admin->is_active ? 'Active' : 'Inactive' }}</span>
                    </div>
                    <div class="field-grid"><label class="field">Role<select name="role">
                                @foreach (['owner' => 'Owner · full access', 'manager' => 'Manager · catalog & orders', 'orders' => 'Order agent · orders only'] as $value => $label)
                                    <option value="{{ $value }}" @selected($admin->role === $value)>{{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </label><label class="field">Access<select name="is_active">
                                <option value="1" @selected($admin->is_active)>Active</option>
                                <option value="0" @selected(!$admin->is_active)>Inactive</option>
                            </select></label><label class="field">Reset password <span class="muted">Leave blank to
                                keep</span><input name="password" type="password" minlength="12"
                                autocomplete="new-password"></label><label class="field">Confirm new password<input
                                name="password_confirmation" type="password" minlength="12"
                                autocomplete="new-password"></label></div><button
                        class="button button-light small-button">Save team member <x-icon name="check"
                            width="15" /></button>
                </form>
            @endforeach
        </div>
        <aside class="admin-form-secondary">
            <form method="post" action="{{ route('admin.team.store') }}" class="admin-panel form-panel">@csrf<h2>Make room
                    for someone</h2>
                <div class="field-grid single"><label class="field">Name<input name="name" value="{{ old('name') }}"
                            maxlength="120" required></label><label class="field">Email<input name="email" type="email"
                            value="{{ old('email') }}" maxlength="190" required></label><label class="field">Role<select
                            name="role">
                            <option value="orders">Order agent</option>
                            <option value="manager">Manager</option>
                            <option value="owner">Owner</option>
                        </select></label><label class="field">Password<input name="password" type="password" minlength="12"
                            autocomplete="new-password" required></label><label class="field">Confirm password<input
                            name="password_confirmation" type="password" minlength="12" autocomplete="new-password"
                            required></label>
                    <p class="form-hint">Use at least 12 characters with uppercase and lowercase letters, numbers, and
                        symbols.</p><button class="button button-dark">Add team member <x-icon name="plus" /></button>
                </div>
            </form>
            <section class="access-guide">
                <h3>Every role, a clear purpose.</h3>
                <p><strong>Owner</strong> · Full access, settings, and team management.</p>
                <p><strong>Manager</strong> · Catalog, delivery, and all orders.</p>
                <p><strong>Order agent</strong> · View orders, claim unassigned orders, and update their own assigned
                    orders.</p>
            </section>
        </aside>
</div>@endsection
