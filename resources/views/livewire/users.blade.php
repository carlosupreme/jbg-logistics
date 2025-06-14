<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\User;
use App\Models\Store;
use App\Utils\Countries;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

new class extends Component {
    use WithPagination;

    public $search = '';
    public $statusFilter = 'all'; // all, active, inactive
    public $roleFilter = '';
    public $storeFilter = '';

    public $showModal = false;
    public $editingUser = null;

    // User form fields
    public $name = '';
    public $email = '';
    public $password = '';
    public $password_confirmation = '';
    public $phone = '';
    public $mobile = '';
    public $store_id = '';
    public $address = '';
    public $address_2 = '';
    public $city = '';
    public $state = '';
    public $country = '';
    public $postal_code = '';
    public $is_active = true;
    public $selectedRole = '';

    public $showDeleteModal = false;
    public $userToDelete = null;
    public $showPasswordModal = false;
    public $userToResetPassword = null;
    public $newPassword = '';
    public $newPassword_confirmation = '';

    public function openCreateModal()
    {
        $this->reset([
            'name', 'email', 'password', 'password_confirmation', 'phone', 'mobile',
            'store_id', 'address', 'address_2', 'city', 'state', 'country',
            'postal_code', 'selectedRole', 'editingUser'
        ]);
        $this->is_active = true;
        $this->showModal = true;
    }

    public function openEditModal($userId)
    {
        $user = User::with(['store', 'roles'])->findOrFail($userId);
        $this->editingUser = $user;

        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone = $user->phone ?? '';
        $this->mobile = $user->mobile ?? '';
        $this->store_id = $user->store_id ?? '';
        $this->address = $user->address ?? '';
        $this->address_2 = $user->address_2 ?? '';
        $this->city = $user->city ?? '';
        $this->state = $user->state ?? '';
        $this->country = $user->country ?? '';
        $this->postal_code = $user->postal_code ?? '';
        $this->is_active = $user->is_active;
        $this->selectedRole = $user->roles->first()?->name ?? '';

        $this->password = '';
        $this->password_confirmation = '';

        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->reset([
            'name', 'email', 'password', 'password_confirmation', 'phone', 'mobile',
            'store_id', 'address', 'address_2', 'city', 'state', 'country',
            'postal_code', 'selectedRole', 'editingUser'
        ]);
    }

    public function save()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email' . ($this->editingUser ? ',' . $this->editingUser->id : ''),
            'phone' => 'nullable|string|max:255',
            'mobile' => 'nullable|string|max:255',
            'store_id' => 'nullable|exists:stores,id',
            'address' => 'nullable|string|max:255',
            'address_2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:3',
            'postal_code' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'selectedRole' => 'nullable|exists:roles,name',
        ];

        // Password validation
        if ($this->editingUser) {
            // For editing, password is optional
            if (!empty($this->password)) {
                $rules['password'] = 'required|string|min:8|confirmed';
            }
        } else {
            // For creating, password is required
            $rules['password'] = 'required|string|min:8|confirmed';
        }

        $this->validate($rules, [
            'name.required' => 'El nombre es requerido.',
            'email.required' => 'El correo electrónico es requerido.',
            'email.email' => 'El correo electrónico debe ser válido.',
            'email.unique' => 'Ya existe un usuario con este correo electrónico.',
            'password.required' => 'La contraseña es requerida.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'store_id.exists' => 'La tienda seleccionada no existe.',
            'selectedRole.exists' => 'El rol seleccionado no existe.',
        ]);

        $userData = [
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'mobile' => $this->mobile,
            'store_id' => $this->store_id ?: null,
            'address' => $this->address,
            'address_2' => $this->address_2,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
            'postal_code' => $this->postal_code,
            'is_active' => $this->is_active,
        ];

        if ($this->editingUser) {
            // Update existing user
            if (!empty($this->password)) {
                $userData['password'] = Hash::make($this->password);
            }

            $this->editingUser->update($userData);

            // Update role
            if ($this->selectedRole) {
                $this->editingUser->syncRoles([$this->selectedRole]);
            } else {
                $this->editingUser->syncRoles([]);
            }

            session()->flash('message', 'Usuario actualizado exitosamente.');
        } else {
            // Create new user
            $userData['password'] = Hash::make($this->password);

            $user = User::create($userData);

            // Assign role
            if ($this->selectedRole) {
                $user->assignRole($this->selectedRole);
            }

            session()->flash('message', 'Usuario creado exitosamente.');
        }

        $this->closeModal();
    }

    public function toggleUserStatus($userId)
    {
        $user = User::findOrFail($userId);
        $user->update(['is_active' => !$user->is_active]);

        $status = $user->is_active ? 'activado' : 'desactivado';
        session()->flash('message', "Usuario {$status} exitosamente.");
    }

    public function confirmDelete($userId)
    {
        $this->userToDelete = User::findOrFail($userId);
        $this->showDeleteModal = true;
    }

    public function delete()
    {
        if ($this->userToDelete) {
            $userName = $this->userToDelete->name;
            $this->userToDelete->delete();
            session()->flash('message', "Usuario '{$userName}' eliminado exitosamente.");
        }

        $this->showDeleteModal = false;
        $this->userToDelete = null;
    }

    public function openPasswordModal($userId)
    {
        $this->userToResetPassword = User::findOrFail($userId);
        $this->newPassword = '';
        $this->newPassword_confirmation = '';
        $this->showPasswordModal = true;
    }

    public function resetPassword()
    {
        $this->validate([
            'newPassword' => 'required|string|min:8|confirmed',
        ], [
            'newPassword.required' => 'La nueva contraseña es requerida.',
            'newPassword.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'newPassword.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        if ($this->userToResetPassword) {
            $this->userToResetPassword->update([
                'password' => Hash::make($this->newPassword),
            ]);

            session()->flash('message', 'Contraseña actualizada exitosamente.');
        }

        $this->showPasswordModal = false;
        $this->userToResetPassword = null;
        $this->newPassword = '';
        $this->newPassword_confirmation = '';
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedStatusFilter()
    {
        $this->resetPage();
    }

    public function updatedRoleFilter()
    {
        $this->resetPage();
    }

    public function updatedStoreFilter()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->statusFilter = 'all';
        $this->roleFilter = '';
        $this->storeFilter = '';
        $this->resetPage();
    }

    public function with(): array
    {
        $users = User::with(['store', 'roles'])
                     ->when($this->search, function ($query) {
                         $query->where(function ($q) {
                             $q->where('name', 'like', '%' . $this->search . '%')
                               ->orWhere('email', 'like', '%' . $this->search . '%');
                         });
                     })
                     ->when($this->statusFilter !== 'all', function ($query) {
                         $query->where('is_active', $this->statusFilter === 'active');
                     })
                     ->when($this->roleFilter, function ($query) {
                         $query->whereHas('roles', function ($q) {
                             $q->where('name', $this->roleFilter);
                         });
                     })
                     ->when($this->storeFilter, function ($query) {
                         $query->where('store_id', $this->storeFilter);
                     })
                     ->latest()
                     ->paginate(10);

        return [
            'users' => $users,
            'stores' => Store::all(),
            'roles' => Role::all(),
            'countries' => Countries::all(),
        ];
    }

    // Define which properties should be included in the query string for pagination
    protected function getQueryString()
    {
        return [
            'search' => ['except' => ''],
            'statusFilter' => ['except' => 'all'],
            'roleFilter' => ['except' => ''],
            'storeFilter' => ['except' => ''],
        ];
    }
}; ?>

<div class="min-h-screen bg-slate-50 dark:bg-gray-900" x-data="{}">
    <div class="p-8 max-w-7xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                <div class="mb-6 sm:mb-0">
                    <h1 class="text-2xl font-light text-slate-900 dark:text-gray-100">Usuarios</h1>
                    <p class="mt-1 text-sm text-slate-500 dark:text-gray-400">Gestión de usuarios del sistema</p>
                </div>
                <div class="flex items-center space-x-3">
                    <button wire:click="clearFilters" 
                            class="inline-flex items-center px-4 py-2 text-sm font-medium text-slate-600 dark:text-gray-300 
                                   bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-lg 
                                   hover:bg-slate-50 dark:hover:bg-gray-700 transition-colors duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Limpiar
                    </button>
                    <button wire:click="openCreateModal" 
                            class="inline-flex items-center px-4 py-2 text-sm font-medium text-white 
                                   bg-blue-500 dark:bg-blue-600 rounded-lg hover:bg-blue-600 dark:hover:bg-blue-700 
                                   transition-colors duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        Nuevo Usuario
                    </button>
                </div>
            </div>
        </div>

        <!-- Flash Messages -->
        @if (session()->has('message'))
            <div class="mb-6">
                <div class="bg-blue-50 dark:bg-blue-900/50 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-blue-400 dark:text-blue-300 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-sm text-blue-800 dark:text-blue-200">{{ session('message') }}</p>
                    </div>
                </div>
            </div>
        @endif

        @if (session()->has('error'))
            <div class="mb-6">
                <div class="bg-red-50 dark:bg-red-900/50 border border-red-200 dark:border-red-800 rounded-lg p-4">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-red-400 dark:text-red-300 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-sm text-red-800 dark:text-red-200">{{ session('error') }}</p>
                    </div>
                </div>
            </div>
        @endif

        <!-- Filters -->
        <div class="bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-lg mb-6">
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <!-- Search -->
                    <div>
                        <label class="block text-xs font-medium text-slate-700 dark:text-gray-300 mb-2 uppercase tracking-wide">Buscar</label>
                        <div class="relative">
                            <input wire:model.live.debounce.300ms="search" 
                                   type="text" 
                                   placeholder="Nombre o email..."
                                   class="w-full pl-9 pr-4 py-2 text-sm border border-slate-200 dark:border-gray-600 rounded-md 
                                          focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200
                                          bg-white dark:bg-gray-800 text-slate-900 dark:text-gray-100
                                          placeholder-slate-400 dark:placeholder-gray-500">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center">
                                <svg class="h-4 w-4 text-slate-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Status Filter -->
                    <div>
                        <label class="block text-xs font-medium text-slate-700 dark:text-gray-300 mb-2 uppercase tracking-wide">Estado</label>
                        <select wire:model.live="statusFilter" 
                                class="w-full px-3 py-2 text-sm border border-slate-200 dark:border-gray-600 rounded-md 
                                       focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200
                                       bg-white dark:bg-gray-800 text-slate-900 dark:text-gray-100">
                            <option value="all">Todos</option>
                            <option value="active">Activos</option>
                            <option value="inactive">Inactivos</option>
                        </select>
                    </div>

                    <!-- Role Filter -->
                    <div>
                        <label class="block text-xs font-medium text-slate-700 dark:text-gray-300 mb-2 uppercase tracking-wide">Rol</label>
                        <select wire:model.live="roleFilter" 
                                class="w-full px-3 py-2 text-sm border border-slate-200 dark:border-gray-600 rounded-md 
                                       focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200
                                       bg-white dark:bg-gray-800 text-slate-900 dark:text-gray-100">
                            <option value="">Todos</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role->name }}">{{ $role->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Store Filter -->
                    <div>
                        <label class="block text-xs font-medium text-slate-700 dark:text-gray-300 mb-2 uppercase tracking-wide">Tienda</label>
                        <select wire:model.live="storeFilter" 
                                class="w-full px-3 py-2 text-sm border border-slate-200 dark:border-gray-600 rounded-md 
                                       focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200
                                       bg-white dark:bg-gray-800 text-slate-900 dark:text-gray-100">
                            <option value="">Todas</option>
                            @foreach ($stores as $store)
                                <option value="{{ $store->id }}">{{ $store->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Users Table -->
        <div class="bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-slate-50 dark:bg-gray-900/50 border-b border-slate-200 dark:border-gray-700">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-medium text-slate-500 dark:text-gray-400 uppercase tracking-wider">Usuario</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-slate-500 dark:text-gray-400 uppercase tracking-wider">Rol</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-slate-500 dark:text-gray-400 uppercase tracking-wider">Tienda</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-slate-500 dark:text-gray-400 uppercase tracking-wider">Contacto</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-slate-500 dark:text-gray-400 uppercase tracking-wider">Estado</th>
                            <th class="px-6 py-4 text-right text-xs font-medium text-slate-500 dark:text-gray-400 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-gray-700">
                        @forelse ($users as $user)
                            <tr class="hover:bg-slate-50 dark:hover:bg-gray-700/50 transition-colors duration-150">
                                <!-- User Info -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <div class="h-10 w-10 rounded-full bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center">
                                                <span class="text-sm font-medium text-blue-600 dark:text-blue-300">
                                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-slate-900 dark:text-gray-100">{{ $user->name }}</div>
                                            <div class="text-sm text-slate-500 dark:text-gray-400">{{ $user->email }}</div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Role -->
                                <td class="px-6 py-4">
                                    @if ($user->roles->isNotEmpty())
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                                     bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-200">
                                            {{ $user->roles->first()->name }}
                                        </span>
                                    @else
                                        <span class="text-sm text-slate-400 dark:text-gray-500">Sin rol</span>
                                    @endif
                                </td>

                                <!-- Store -->
                                <td class="px-6 py-4 text-sm text-slate-900 dark:text-gray-100">
                                    {{ $user->store?->name ?? 'Sin asignar' }}
                                </td>

                                <!-- Contact -->
                                <td class="px-6 py-4 text-sm text-slate-500 dark:text-gray-400">
                                    @if($user->phone || $user->mobile)
                                        <div class="space-y-1">
                                            @if($user->phone)
                                                <div>{{ $user->phone }}</div>
                                            @endif
                                            @if($user->mobile)
                                                <div>{{ $user->mobile }}</div>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-slate-400 dark:text-gray-500">—</span>
                                    @endif
                                </td>

                                <!-- Status -->
                                <td class="px-6 py-4">
                                    @if ($user->is_active)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                                     bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-200">
                                            <div class="w-1.5 h-1.5 bg-green-400 dark:bg-green-300 rounded-full mr-2"></div>
                                            Activo
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                                     bg-red-100 dark:bg-red-900/50 text-red-800 dark:text-red-200">
                                            <div class="w-1.5 h-1.5 bg-red-400 dark:bg-red-300 rounded-full mr-2"></div>
                                            Inactivo
                                        </span>
                                    @endif
                                </td>

                                <!-- Actions -->
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end items-center space-x-2">
                                        <!-- Edit -->
                                        <button wire:click="openEditModal({{ $user->id }})"
                                                class="p-1.5 text-slate-400 dark:text-gray-500 hover:text-blue-600 dark:hover:text-blue-400 transition-colors duration-200"
                                                title="Editar">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </button>

                                        <!-- Toggle Status -->
                                        <button wire:click="toggleUserStatus({{ $user->id }})"
                                                class="p-1.5 text-slate-400 dark:text-gray-500 hover:text-{{ $user->is_active ? 'amber' : 'green' }}-600 dark:hover:text-{{ $user->is_active ? 'amber' : 'green' }}-400 transition-colors duration-200"
                                                title="{{ $user->is_active ? 'Desactivar' : 'Activar' }}">
                                            @if($user->is_active)
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L18.364 5.636"/>
                                                </svg>
                                            @else
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                            @endif
                                        </button>

                                        <!-- Reset Password -->
                                        <button wire:click="openPasswordModal({{ $user->id }})"
                                                class="p-1.5 text-slate-400 dark:text-gray-500 hover:text-blue-600 dark:hover:text-blue-400 transition-colors duration-200"
                                                title="Cambiar contraseña">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                                            </svg>
                                        </button>

                                        <!-- Delete -->
                                        <button wire:click="confirmDelete({{ $user->id }})"
                                                class="p-1.5 text-slate-400 dark:text-gray-500 hover:text-red-600 dark:hover:text-red-400 transition-colors duration-200"
                                                title="Eliminar">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center">
                                        <div class="w-16 h-16 bg-slate-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4">
                                            <svg class="w-8 h-8 text-slate-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                            </svg>
                                        </div>
                                        <h3 class="text-lg font-medium text-slate-900 dark:text-gray-100 mb-2">No hay usuarios</h3>
                                        <p class="text-slate-500 dark:text-gray-400 mb-4">No se encontraron usuarios que coincidan con los filtros.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($users->hasPages())
                <div class="px-6 py-4 border-t border-slate-200 dark:border-gray-700 bg-slate-50 dark:bg-gray-900/50">
                    {{ $users->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- Create/Edit Modal -->
    @if ($showModal)
        <div class="fixed inset-0 bg-slate-600/50 dark:bg-gray-900/75 overflow-y-auto h-full w-full z-50"
             x-data x-show="true" x-transition>
            <div class="relative top-4 mx-auto p-0 w-11/12 md:w-4/5 lg:w-3/4 xl:w-2/3 max-w-6xl">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-h-[90vh] overflow-hidden border border-slate-200 dark:border-gray-700">
                    <!-- Modal Header -->
                    <div class="border-b border-slate-200 dark:border-gray-700 px-6 py-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-medium text-slate-900 dark:text-gray-100">
                                    {{ $editingUser ? 'Editar Usuario' : 'Nuevo Usuario' }}
                                </h3>
                                <p class="text-sm text-slate-500 dark:text-gray-400 mt-1">
                                    {{ $editingUser ? 'Modifica la información del usuario' : 'Completa los datos para crear un nuevo usuario' }}
                                </p>
                            </div>
                            <button wire:click="closeModal" 
                                    class="text-slate-400 dark:text-gray-500 hover:text-slate-600 dark:hover:text-gray-300 transition-colors duration-200">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Modal Body -->
                    <div class="p-6 overflow-y-auto max-h-[70vh]">
                        <form wire:submit="save">
                            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                                <!-- Left Column - Basic Info -->
                                <div class="space-y-6">
                                    <div>
                                        <h4 class="text-sm font-medium text-slate-900 dark:text-gray-100 mb-4">Información Personal</h4>

                                        @if ($editingUser)
                                            <div class="mb-4">
                                                <label class="block text-xs font-medium text-slate-700 dark:text-gray-300 mb-2 uppercase tracking-wide">ID Usuario</label>
                                                <div class="px-3 py-2 bg-slate-50 dark:bg-gray-700 border border-slate-200 dark:border-gray-600 rounded-md text-slate-600 dark:text-gray-300 text-sm">
                                                    {{ $editingUser->formatted_id ?? '#' . $editingUser->id }}
                                                </div>
                                            </div>
                                        @endif

                                        <div class="space-y-4">
                                            <div>
                                                <label for="name" class="block text-xs font-medium text-slate-700 dark:text-gray-300 mb-2 uppercase tracking-wide">
                                                    Nombre Completo *
                                                </label>
                                                <input wire:model="name" type="text" id="name"
                                                       class="w-full px-3 py-2 text-sm border border-slate-200 dark:border-gray-600 rounded-md 
                                                              focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200
                                                              bg-white dark:bg-gray-800 text-slate-900 dark:text-gray-100
                                                              placeholder-slate-400 dark:placeholder-gray-500"
                                                       placeholder="Nombre completo">
                                                @error('name') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                                            </div>

                                            <div>
                                                <label for="email" class="block text-xs font-medium text-slate-700 dark:text-gray-300 mb-2 uppercase tracking-wide">
                                                    Email *
                                                </label>
                                                <input wire:model="email" type="email" id="email"
                                                       class="w-full px-3 py-2 text-sm border border-slate-200 dark:border-gray-600 rounded-md 
                                                              focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200
                                                              bg-white dark:bg-gray-800 text-slate-900 dark:text-gray-100
                                                              placeholder-slate-400 dark:placeholder-gray-500"
                                                       placeholder="email@ejemplo.com">
                                                @error('email') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                                            </div>

                                            <div>
                                                <label for="phone" class="block text-xs font-medium text-slate-700 dark:text-gray-300 mb-2 uppercase tracking-wide">Teléfono</label>
                                                <input wire:model="phone" type="text" id="phone"
                                                       class="w-full px-3 py-2 text-sm border border-slate-200 dark:border-gray-600 rounded-md 
                                                              focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200
                                                              bg-white dark:bg-gray-800 text-slate-900 dark:text-gray-100
                                                              placeholder-slate-400 dark:placeholder-gray-500">
                                                @error('phone') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                                            </div>

                                            <div>
                                                <label for="mobile" class="block text-xs font-medium text-slate-700 dark:text-gray-300 mb-2 uppercase tracking-wide">Móvil</label>
                                                <input wire:model="mobile" type="text" id="mobile"
                                                       class="w-full px-3 py-2 text-sm border border-slate-200 dark:border-gray-600 rounded-md 
                                                              focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200
                                                              bg-white dark:bg-gray-800 text-slate-900 dark:text-gray-100
                                                              placeholder-slate-400 dark:placeholder-gray-500">
                                                @error('mobile') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                                            </div>

                                            <div>
                                                <label for="selectedRole" class="block text-xs font-medium text-slate-700 dark:text-gray-300 mb-2 uppercase tracking-wide">Rol</label>
                                                <select wire:model="selectedRole" id="selectedRole"
                                                        class="w-full px-3 py-2 text-sm border border-slate-200 dark:border-gray-600 rounded-md 
                                                               focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200
                                                               bg-white dark:bg-gray-800 text-slate-900 dark:text-gray-100">
                                                    <option value="">Seleccionar rol</option>
                                                    @foreach ($roles as $role)
                                                        <option value="{{ $role->name }}">{{ $role->name }}</option>
                                                    @endforeach
                                                </select>
                                                @error('selectedRole') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                                            </div>

                                            <div>
                                                <label for="store_id" class="block text-xs font-medium text-slate-700 dark:text-gray-300 mb-2 uppercase tracking-wide">Tienda</label>
                                                <select wire:model="store_id" id="store_id"
                                                        class="w-full px-3 py-2 text-sm border border-slate-200 dark:border-gray-600 rounded-md 
                                                               focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200
                                                               bg-white dark:bg-gray-800 text-slate-900 dark:text-gray-100">
                                                    <option value="">Seleccionar tienda</option>
                                                    @foreach ($stores as $store)
                                                        <option value="{{ $store->id }}">{{ $store->name }}</option>
                                                    @endforeach
                                                </select>
                                                @error('store_id') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                                            </div>

                                            <div class="flex items-center">
                                                <input wire:model="is_active" type="checkbox" id="is_active"
                                                       class="h-4 w-4 text-blue-600 dark:text-blue-500 border-slate-300 dark:border-gray-600 rounded 
                                                              focus:ring-blue-500 dark:focus:ring-blue-400 bg-white dark:bg-gray-800">
                                                <label for="is_active" class="ml-2 text-sm text-slate-700 dark:text-gray-300">
                                                    Usuario activo
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Middle Column - Address -->
                                <div class="space-y-6">
                                    <div>
                                        <h4 class="text-sm font-medium text-slate-900 dark:text-gray-100 mb-4">Dirección</h4>

                                        <div class="space-y-4">
                                            <div>
                                                <label for="address" class="block text-xs font-medium text-slate-700 dark:text-gray-300 mb-2 uppercase tracking-wide">Dirección</label>
                                                <input wire:model="address" type="text" id="address"
                                                       class="w-full px-3 py-2 text-sm border border-slate-200 dark:border-gray-600 rounded-md 
                                                              focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200
                                                              bg-white dark:bg-gray-800 text-slate-900 dark:text-gray-100
                                                              placeholder-slate-400 dark:placeholder-gray-500">
                                                @error('address') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                                            </div>

                                            <div>
                                                <label for="address_2" class="block text-xs font-medium text-slate-700 dark:text-gray-300 mb-2 uppercase tracking-wide">Dirección 2</label>
                                                <input wire:model="address_2" type="text" id="address_2"
                                                       class="w-full px-3 py-2 text-sm border border-slate-200 dark:border-gray-600 rounded-md 
                                                              focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200
                                                              bg-white dark:bg-gray-800 text-slate-900 dark:text-gray-100
                                                              placeholder-slate-400 dark:placeholder-gray-500">
                                                @error('address_2') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                                            </div>

                                            <div class="grid grid-cols-2 gap-4">
                                                <div>
                                                    <label for="city" class="block text-xs font-medium text-slate-700 dark:text-gray-300 mb-2 uppercase tracking-wide">Ciudad</label>
                                                    <input wire:model="city" type="text" id="city"
                                                           class="w-full px-3 py-2 text-sm border border-slate-200 dark:border-gray-600 rounded-md 
                                                                  focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200
                                                                  bg-white dark:bg-gray-800 text-slate-900 dark:text-gray-100
                                                                  placeholder-slate-400 dark:placeholder-gray-500">
                                                    @error('city') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                                                </div>

                                                <div>
                                                    <label for="state" class="block text-xs font-medium text-slate-700 dark:text-gray-300 mb-2 uppercase tracking-wide">Estado</label>
                                                    <input wire:model="state" type="text" id="state"
                                                           class="w-full px-3 py-2 text-sm border border-slate-200 dark:border-gray-600 rounded-md 
                                                                  focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200
                                                                  bg-white dark:bg-gray-800 text-slate-900 dark:text-gray-100
                                                                  placeholder-slate-400 dark:placeholder-gray-500">
                                                    @error('state') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                                                </div>
                                            </div>

                                            <div class="grid grid-cols-2 gap-4">
                                                <div>
                                                    <label for="country" class="block text-xs font-medium text-slate-700 dark:text-gray-300 mb-2 uppercase tracking-wide">País</label>
                                                    <select wire:model="country" id="country"
                                                            class="w-full px-3 py-2 text-sm border border-slate-200 dark:border-gray-600 rounded-md 
                                                                   focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200
                                                                   bg-white dark:bg-gray-800 text-slate-900 dark:text-gray-100">
                                                        <option value="">Seleccionar país</option>
                                                        @foreach ($countries as $code => $name)
                                                            <option value="{{ $code }}">{{ $name }}</option>
                                                        @endforeach
                                                    </select>
                                                    @error('country') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                                                </div>

                                                <div>
                                                    <label for="postal_code" class="block text-xs font-medium text-slate-700 dark:text-gray-300 mb-2 uppercase tracking-wide">Código Postal</label>
                                                    <input wire:model="postal_code" type="text" id="postal_code"
                                                           class="w-full px-3 py-2 text-sm border border-slate-200 dark:border-gray-600 rounded-md 
                                                                  focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200
                                                                  bg-white dark:bg-gray-800 text-slate-900 dark:text-gray-100
                                                                  placeholder-slate-400 dark:placeholder-gray-500">
                                                    @error('postal_code') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Right Column - Security -->
                                <div class="space-y-6">
                                    <div>
                                        <h4 class="text-sm font-medium text-slate-900 dark:text-gray-100 mb-4">
                                            {{ $editingUser ? 'Cambiar Contraseña' : 'Contraseña' }}
                                        </h4>

                                        <div class="space-y-4">
                                            <div>
                                                <label for="password" class="block text-xs font-medium text-slate-700 dark:text-gray-300 mb-2 uppercase tracking-wide">
                                                    {{ $editingUser ? 'Nueva Contraseña' : 'Contraseña' }}
                                                    @if(!$editingUser) * @endif
                                                </label>
                                                <input wire:model="password" type="password" id="password"
                                                       class="w-full px-3 py-2 text-sm border border-slate-200 dark:border-gray-600 rounded-md 
                                                              focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200
                                                              bg-white dark:bg-gray-800 text-slate-900 dark:text-gray-100
                                                              placeholder-slate-400 dark:placeholder-gray-500">
                                                @error('password') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                                            </div>

                                            <div>
                                                <label for="password_confirmation" class="block text-xs font-medium text-slate-700 dark:text-gray-300 mb-2 uppercase tracking-wide">
                                                    Confirmar Contraseña
                                                    @if(!$editingUser) * @endif
                                                </label>
                                                <input wire:model="password_confirmation" type="password" id="password_confirmation"
                                                       class="w-full px-3 py-2 text-sm border border-slate-200 dark:border-gray-600 rounded-md 
                                                              focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200
                                                              bg-white dark:bg-gray-800 text-slate-900 dark:text-gray-100
                                                              placeholder-slate-400 dark:placeholder-gray-500">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Modal Footer -->
                            <div class="flex justify-end space-x-3 mt-8 pt-6 border-t border-slate-200 dark:border-gray-700">
                                <button type="button" wire:click="closeModal"
                                        class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-gray-300 
                                               bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-600 rounded-md 
                                               hover:bg-slate-50 dark:hover:bg-gray-700 transition-colors duration-200">
                                    Cancelar
                                </button>
                                <button type="submit"
                                        class="px-4 py-2 text-sm font-medium text-white 
                                               bg-blue-500 dark:bg-blue-600 rounded-md hover:bg-blue-600 dark:hover:bg-blue-700 
                                               transition-colors duration-200">
                                    {{ $editingUser ? 'Actualizar' : 'Crear' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Delete Confirmation Modal -->
    @if ($showDeleteModal && $userToDelete)
        <div class="fixed inset-0 bg-slate-600/50 dark:bg-gray-900/75 overflow-y-auto h-full w-full z-50"
             x-data x-show="true" x-transition>
            <div class="relative top-20 mx-auto p-0 w-full max-w-md">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl mx-4 border border-slate-200 dark:border-gray-700">
                    <div class="p-6">
                        <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 dark:bg-red-900/50 rounded-full mb-4">
                            <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-4.69 4.12L12 21l4.69-1.88A2 2 0 0018 17.3V9.5a2 2 0 00-1.2-1.84L12 6l-4.8 1.66A2 2 0 006 9.5v7.8a2 2 0 001.31 1.82z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-slate-900 dark:text-gray-100 text-center mb-2">Confirmar Eliminación</h3>
                        <p class="text-slate-600 dark:text-gray-400 text-center mb-6">
                            ¿Estás seguro de que deseas eliminar al usuario 
                            <span class="font-medium text-slate-900 dark:text-gray-100">"{{ $userToDelete->name }}"</span>?
                            <br><span class="text-sm text-red-600 dark:text-red-400 font-medium">Esta acción no se puede deshacer.</span>
                        </p>
                        <div class="flex space-x-3">
                            <button wire:click="$set('showDeleteModal', false)"
                                    class="flex-1 px-4 py-2 text-sm font-medium text-slate-700 dark:text-gray-300 
                                           bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-600 rounded-md 
                                           hover:bg-slate-50 dark:hover:bg-gray-700 transition-colors duration-200">
                                Cancelar
                            </button>
                            <button wire:click="delete"
                                    class="flex-1 px-4 py-2 text-sm font-medium text-white 
                                           bg-red-600 dark:bg-red-700 rounded-md hover:bg-red-700 dark:hover:bg-red-800 
                                           transition-colors duration-200">
                                Eliminar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Password Reset Modal -->
    @if ($showPasswordModal && $userToResetPassword)
        <div class="fixed inset-0 bg-slate-600/50 dark:bg-gray-900/75 overflow-y-auto h-full w-full z-50"
             x-data x-show="true" x-transition>
            <div class="relative top-20 mx-auto p-0 w-full max-w-md">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl mx-4 border border-slate-200 dark:border-gray-700">
                    <div class="p-6">
                        <div class="flex items-center justify-center w-12 h-12 mx-auto bg-blue-100 dark:bg-blue-900/50 rounded-full mb-4">
                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-slate-900 dark:text-gray-100 text-center mb-2">Cambiar Contraseña</h3>
                        <p class="text-slate-600 dark:text-gray-400 text-center mb-6">
                            Usuario: <span class="font-medium text-slate-900 dark:text-gray-100">{{ $userToResetPassword->name }}</span>
                        </p>

                        <form wire:submit="resetPassword" class="space-y-4">
                            <div>
                                <label for="newPassword" class="block text-xs font-medium text-slate-700 dark:text-gray-300 mb-2 uppercase tracking-wide">
                                    Nueva Contraseña *
                                </label>
                                <input wire:model="newPassword" type="password" id="newPassword"
                                       class="w-full px-3 py-2 text-sm border border-slate-200 dark:border-gray-600 rounded-md 
                                              focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200
                                              bg-white dark:bg-gray-800 text-slate-900 dark:text-gray-100
                                              placeholder-slate-400 dark:placeholder-gray-500">
                                @error('newPassword') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label for="newPassword_confirmation" class="block text-xs font-medium text-slate-700 dark:text-gray-300 mb-2 uppercase tracking-wide">
                                    Confirmar Contraseña *
                                </label>
                                <input wire:model="newPassword_confirmation" type="password" id="newPassword_confirmation"
                                       class="w-full px-3 py-2 text-sm border border-slate-200 dark:border-gray-600 rounded-md 
                                              focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200
                                              bg-white dark:bg-gray-800 text-slate-900 dark:text-gray-100
                                              placeholder-slate-400 dark:placeholder-gray-500">
                            </div>

                            <div class="flex space-x-3 pt-4">
                                <button type="button" wire:click="$set('showPasswordModal', false)"
                                        class="flex-1 px-4 py-2 text-sm font-medium text-slate-700 dark:text-gray-300 
                                               bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-600 rounded-md 
                                               hover:bg-slate-50 dark:hover:bg-gray-700 transition-colors duration-200">
                                    Cancelar
                                </button>
                                <button type="submit"
                                        class="flex-1 px-4 py-2 text-sm font-medium text-white 
                                               bg-blue-500 dark:bg-blue-600 rounded-md hover:bg-blue-600 dark:hover:bg-blue-700 
                                               transition-colors duration-200">
                                    Actualizar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
