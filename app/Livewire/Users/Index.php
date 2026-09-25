<?php

namespace App\Livewire\Users;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public ?int $editingUserId = null;

    public bool $creating = false;

    public array $selectedRoles = [];

    public string $search = '';

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->authorize('users.create');
        $this->reset(['editingUserId', 'name', 'email', 'password', 'password_confirmation', 'selectedRoles']);
        $this->creating = true;
    }

    public function editUser(int $userId): void
    {
        $this->authorize('users.update');
        $user = User::with('roles')->findOrFail($userId);
        $this->editingUserId = $userId;
        $this->creating = false;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->password = '';
        $this->password_confirmation = '';
        $this->selectedRoles = $user->roles->pluck('id')->map(fn ($id) => (string) $id)->toArray();
    }

    public function saveCreate(): void
    {
        $this->authorize('users.create');
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'selectedRoles' => ['array'],
        ]);

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => Hash::make($this->password),
        ]);

        $user->syncRoles(Role::whereIn('id', $this->selectedRoles)->get());
        $this->closeModal();
    }

    public function saveEdit(): void
    {
        $this->authorize('users.update');
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', "unique:users,email,{$this->editingUserId}"],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'selectedRoles' => ['array'],
        ]);

        $user = User::findOrFail($this->editingUserId);
        $user->name = $this->name;
        $user->email = $this->email;

        if ($this->password !== '') {
            $user->password = Hash::make($this->password);
        }

        $user->save();
        $user->syncRoles(Role::whereIn('id', $this->selectedRoles)->get());
        $this->closeModal();
    }

    public function closeModal(): void
    {
        $this->creating = false;
        $this->editingUserId = null;
        $this->reset(['name', 'email', 'password', 'password_confirmation', 'selectedRoles']);
        $this->resetValidation();
    }

    public function render()
    {
        $users = User::query()
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%");
            }))
            ->with('roles')
            ->orderBy('name')
            ->paginate(15);

        $allRoles = Role::orderBy('name')->get();

        return view('livewire.users.index', compact('users', 'allRoles'));
    }
}
