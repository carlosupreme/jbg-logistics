<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

new class extends Component {
    use WithPagination;

    public $search = '';
    public $showModal = false;
    public $editingRole = null;
    public $roleName = '';
    public $selectedPermissions = [];
    public $showDeleteModal = false;
    public $roleToDelete = null;
    public $permissionGroups = [];

    public function mount()
    {
        $this->groupPermissions();
    }

    public function groupPermissions()
    {
        $permissions = Permission::all();
        $grouped = [];

        foreach ($permissions as $permission) {
            $parts = explode('.', $permission->name);
            $module = $parts[0] ?? 'general';
            $action = $parts[1] ?? $permission->name;

            if (!isset($grouped[$module])) {
                $grouped[$module] = [];
            }

            $grouped[$module][] = [
                'id' => $permission->id,
                'name' => $permission->name,
                'action' => $action,
                'display' => $this->getPermissionDisplay($permission->name)
            ];
        }

        $this->permissionGroups = $grouped;
    }

    public function getPermissionDisplay($permissionName)
    {
        $displays = [
            'users.view' => 'Ver Usuarios',
            'users.create' => 'Crear Usuarios',
            'users.edit' => 'Editar Usuarios',
            'users.delete' => 'Eliminar Usuarios',
            'users.activate' => 'Activar Usuarios',
            'users.deactivate' => 'Desactivar Usuarios',
            'users.reset-password' => 'Resetear Contraseñas',
            'stores.view' => 'Ver Tiendas',
            'stores.create' => 'Crear Tiendas',
            'stores.edit' => 'Editar Tiendas',
            'stores.delete' => 'Eliminar Tiendas',
            'zones.view' => 'Ver Zonas',
            'zones.create' => 'Crear Zonas',
            'zones.edit' => 'Editar Zonas',
            'zones.delete' => 'Eliminar Zonas',
            'roles.view' => 'Ver Roles',
            'roles.create' => 'Crear Roles',
            'roles.edit' => 'Editar Roles',
            'roles.delete' => 'Eliminar Roles',
            'permissions.view' => 'Ver Permisos',
            'permissions.assign' => 'Asignar Permisos',
            'reports.view' => 'Ver Reportes',
            'reports.export' => 'Exportar Reportes',
            'analytics.view' => 'Ver Analíticas',
            'settings.view' => 'Ver Configuraciones',
            'settings.edit' => 'Editar Configuraciones',
            'system.maintenance' => 'Mantenimiento del Sistema',
            'logs.view' => 'Ver Logs',
            'logs.export' => 'Exportar Logs',
        ];

        return $displays[$permissionName] ?? ucwords(str_replace(['.', '_'], ' ', $permissionName));
    }

    public function getModuleDisplay($module)
    {
        $modules = [
            'users' => 'Gestión de Usuarios',
            'stores' => 'Gestión de Tiendas',
            'zones' => 'Gestión de Zonas',
            'roles' => 'Gestión de Roles',
            'permissions' => 'Gestión de Permisos',
            'reports' => 'Reportes',
            'analytics' => 'Analíticas',
            'settings' => 'Configuraciones',
            'system' => 'Sistema',
            'logs' => 'Logs de Auditoría',
        ];

        return $modules[$module] ?? ucwords($module);
    }

    public function openCreateModal()
    {
        $this->reset(['roleName', 'selectedPermissions', 'editingRole']);
        $this->showModal = true;
    }

    public function openEditModal($roleId)
    {
        $role = Role::with('permissions')->findOrFail($roleId);
        $this->editingRole = $role;
        $this->roleName = $role->name;
        $this->selectedPermissions = $role->permissions->pluck('name')->toArray();
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->reset(['roleName', 'selectedPermissions', 'editingRole']);
    }

    public function save()
    {
        $this->validate([
            'roleName' => 'required|string|max:255|unique:roles,name' . ($this->editingRole ? ',' . $this->editingRole->id : ''),
            'selectedPermissions' => 'array',
        ], [
            'roleName.required' => 'El nombre del rol es requerido.',
            'roleName.unique' => 'Ya existe un rol con este nombre.',
        ]);

        if ($this->editingRole) {
            // Update existing role
            $this->editingRole->update(['name' => $this->roleName]);
            $this->editingRole->syncPermissions($this->selectedPermissions);

            session()->flash('message', 'Rol actualizado exitosamente.');
        } else {
            // Create new role
            $role = Role::create(['name' => $this->roleName]);
            $role->syncPermissions($this->selectedPermissions);

            session()->flash('message', 'Rol creado exitosamente.');
        }

        $this->closeModal();
    }

    public function confirmDelete($roleId)
    {
        $this->roleToDelete = Role::findOrFail($roleId);
        $this->showDeleteModal = true;
    }

    public function delete()
    {
        if ($this->roleToDelete) {
            // Check if role is being used
            $usersCount = $this->roleToDelete->users()->count();

            if ($usersCount > 0) {
                session()->flash('error', "No se puede eliminar el rol '{$this->roleToDelete->name}' porque está asignado a {$usersCount} usuario(s).");
            } else {
                $roleName = $this->roleToDelete->name;
                $this->roleToDelete->delete();
                session()->flash('message', "Rol '{$roleName}' eliminado exitosamente.");
            }
        }

        $this->showDeleteModal = false;
        $this->roleToDelete = null;
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function with(): array
    {
        $roles = Role::with('permissions')
                     ->when($this->search, function ($query) {
                         $query->where('name', 'like', '%' . $this->search . '%');
                     })
                     ->paginate(10);

        return [
            'roles' => $roles,
        ];
    }
}; ?>

<div class="p-6" x-data="{}">
    <!-- Header -->
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Gestión de Roles</h1>
            <p class="text-gray-600 dark:text-gray-400">Administra los roles y sus permisos</p>
        </div>
        <button wire:click="openCreateModal" 
                class="bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white px-4 py-2 rounded-lg flex items-center gap-2 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            Nuevo Rol
        </button>
    </div>

    <!-- Search -->
    <div class="mb-6">
        <div class="relative">
            <input wire:model.live.debounce.300ms="search" 
                   type="text" 
                   placeholder="Buscar roles..."
                   class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg 
                          focus:ring-2 focus:ring-blue-500 focus:border-transparent 
                          bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 
                          placeholder-gray-500 dark:placeholder-gray-400">
            <svg class="absolute left-3 top-2.5 h-5 w-5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </div>
    </div>

    <!-- Flash Messages -->
    @if (session()->has('message'))
        <div class="mb-4 bg-green-50 dark:bg-green-900/50 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-300 px-4 py-3 rounded-lg">
            {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="mb-4 bg-red-50 dark:bg-red-900/50 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 px-4 py-3 rounded-lg">
            {{ session('error') }}
        </div>
    @endif

    <!-- Roles Table -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900/50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Rol</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Permisos</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Usuarios</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                @forelse ($roles as $role)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="font-medium text-gray-900 dark:text-gray-100">{{ $role->name }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex flex-wrap gap-1">
                                @foreach ($role->permissions->take(3) as $permission)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                                 bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-200">
                                        {{ $this->getPermissionDisplay($permission->name) }}
                                    </span>
                                @endforeach
                                @if ($role->permissions->count() > 3)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                                 bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300">
                                        +{{ $role->permissions->count() - 3 }} más
                                    </span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                            {{ $role->users->count() }} usuarios
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex justify-end gap-2">
                                <button wire:click="openEditModal({{ $role->id }})" 
                                        class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300 transition-colors">
                                    Editar
                                </button>
                                <button wire:click="confirmDelete({{ $role->id }})" 
                                        class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300 transition-colors">
                                    Eliminar
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                            No se encontraron roles.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Pagination -->
        <div class="px-6 py-3 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
            {{ $roles->links() }}
        </div>
    </div>

    <!-- Create/Edit Modal -->
    @if ($showModal)
        <div class="fixed inset-0 bg-gray-600/50 dark:bg-gray-900/75 overflow-y-auto h-full w-full z-50" 
             x-data x-show="true" x-transition>
            <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-lg 
                        bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700">
                <div class="mb-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                        {{ $editingRole ? 'Editar Rol' : 'Crear Nuevo Rol' }}
                    </h3>
                </div>

                <form wire:submit="save">
                    <!-- Role Name -->
                    <div class="mb-6">
                        <label for="roleName" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Nombre del Rol *
                        </label>
                        <input wire:model="roleName" 
                               type="text" 
                               id="roleName"
                               placeholder="Ingrese el nombre del rol"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md 
                                      focus:outline-none focus:ring-2 focus:ring-blue-500 
                                      bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100
                                      placeholder-gray-500 dark:placeholder-gray-400">
                        @error('roleName')
                            <span class="text-red-500 dark:text-red-400 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Permissions -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Permisos</label>
                        <div class="max-h-64 overflow-y-auto border border-gray-200 dark:border-gray-600 rounded-md p-4 
                                    bg-gray-50 dark:bg-gray-900/50">
                            @foreach ($permissionGroups as $module => $permissions)
                                <div class="mb-4">
                                    <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-2">
                                        {{ $this->getModuleDisplay($module) }}
                                    </h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                        @foreach ($permissions as $permission)
                                            <label class="flex items-center cursor-pointer">
                                                <input wire:model="selectedPermissions" 
                                                       type="checkbox"
                                                       value="{{ $permission['name'] }}"
                                                       class="rounded border-gray-300 dark:border-gray-600 
                                                              text-blue-600 dark:text-blue-500 
                                                              shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50
                                                              bg-white dark:bg-gray-800">
                                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                                    {{ $permission['display'] }}
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-600">
                        <button type="button" 
                                wire:click="closeModal"
                                class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 
                                       bg-gray-200 dark:bg-gray-700 rounded-md 
                                       hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                            Cancelar
                        </button>
                        <button type="submit"
                                class="px-4 py-2 text-sm font-medium text-white 
                                       bg-blue-600 dark:bg-blue-500 rounded-md 
                                       hover:bg-blue-700 dark:hover:bg-blue-600 transition-colors">
                            {{ $editingRole ? 'Actualizar' : 'Crear' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Delete Confirmation Modal -->
    @if ($showDeleteModal && $roleToDelete)
        <div class="fixed inset-0 bg-gray-600/50 dark:bg-gray-900/75 overflow-y-auto h-full w-full z-50"
             x-data x-show="true" x-transition>
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-lg 
                        bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700">
                <div class="text-center">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                        Confirmar Eliminación
                    </h3>
                    <div class="mb-6">
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            ¿Estás seguro de que deseas eliminar el rol 
                            "<strong class="text-gray-900 dark:text-gray-100">{{ $roleToDelete->name }}</strong>"?
                            <br>Esta acción no se puede deshacer.
                        </p>
                    </div>
                    <div class="flex justify-center gap-3">
                        <button wire:click="$set('showDeleteModal', false)"
                                class="px-4 py-2 bg-gray-500 dark:bg-gray-600 text-white text-base font-medium rounded-md 
                                       shadow-sm hover:bg-gray-700 dark:hover:bg-gray-500 transition-colors">
                            Cancelar
                        </button>
                        <button wire:click="delete"
                                class="px-4 py-2 bg-red-500 dark:bg-red-600 text-white text-base font-medium rounded-md 
                                       shadow-sm hover:bg-red-700 dark:hover:bg-red-500 transition-colors">
                            Eliminar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
