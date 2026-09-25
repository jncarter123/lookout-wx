<?php

namespace App\Livewire\Roles;

use App\Models\Role;
use Livewire\Component;

class Index extends Component
{
    public bool $showForm = false;

    public ?int $editingRoleId = null;

    public string $roleName = '';

    public array $selectedPermissions = [];

    public function newRole(): void
    {
        $this->authorize('roles.create');
        $this->editingRoleId = null;
        $this->roleName = '';
        $this->selectedPermissions = [];
        $this->showForm = true;
    }

    public function editRole(int $roleId): void
    {
        $this->authorize('roles.update');
        $role = Role::with('permissions')->findOrFail($roleId);
        $this->editingRoleId = $roleId;
        $this->roleName = $role->name;
        $this->selectedPermissions = $role->permissions->pluck('name')->toArray();
        $this->showForm = true;
    }

    public function saveRole(): void
    {
        $this->authorize($this->editingRoleId ? 'roles.update' : 'roles.create');
        $this->validate([
            'roleName' => ['required', 'string', 'max:255'],
        ]);

        if ($this->editingRoleId) {
            $role = Role::findOrFail($this->editingRoleId);
            $role->update(['name' => $this->roleName]);
        } else {
            $role = Role::create(['name' => $this->roleName, 'guard_name' => 'web']);
        }

        $role->syncPermissions($this->selectedPermissions);

        $this->cancelForm();
    }

    public function deleteRole(int $roleId): void
    {
        $this->authorize('roles.delete');
        Role::findOrFail($roleId)->delete();
    }

    public function cancelForm(): void
    {
        $this->showForm = false;
        $this->editingRoleId = null;
        $this->roleName = '';
        $this->selectedPermissions = [];
        $this->resetValidation();
    }

    public function render()
    {
        $roles = Role::withCount('users')->with('permissions')->orderBy('name')->get();
        $availablePermissions = config('auth_permissions.permissions', []);

        return view('livewire.roles.index', compact('roles', 'availablePermissions'));
    }
}
