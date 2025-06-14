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
            $this->userToDelete->update(['is_active' => false]);
            session()->flash('message', "Usuario '{$userName}' desactivado exitosamente.");
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
}; ?>

<div class="p-6">
    <!-- Header -->
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Gestión de Usuarios</h1>
            <p class="text-gray-600">Administra usuarios, roles y permisos</p>
        </div>
        <button wire:click="openCreateModal" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            Nuevo Usuario
        </button>
    </div>

    <!-- Filters -->
    <div class="mb-6 grid grid-cols-1 md:grid-cols-4 gap-4">
        <!-- Search -->
        <div class="relative">
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Buscar usuarios..."
                   class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            <svg class="absolute left-3 top-2.5 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </div>

        <!-- Status Filter -->
        <select wire:model.live="statusFilter" class="border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            <option value="all">Todos los estados</option>
            <option value="active">Activos</option>
            <option value="inactive">Inactivos</option>
        </select>

        <!-- Role Filter -->
        <select wire:model.live="roleFilter" class="border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            <option value="">Todos los roles</option>
            @foreach ($roles as $role)
                <option value="{{ $role->name }}">{{ $role->name }}</option>
            @endforeach
        </select>

        <!-- Store Filter -->
        <select wire:model.live="storeFilter" class="border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            <option value="">Todas las tiendas</option>
            @foreach ($stores as $store)
                <option value="{{ $store->id }}">{{ $store->name }}</option>
            @endforeach
        </select>
    </div>

    <!-- Flash Messages -->
    @if (session()->has('message'))
        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
            {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
            {{ session('error') }}
        </div>
    @endif

    <!-- Users Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID Usuario</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usuario</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rol</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tienda</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
            </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
            @forelse ($users as $user)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        {{ $user->formatted_id }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div>
                                <div class="text-sm font-medium text-gray-900">{{ $user->name }}</div>
                                <div class="text-sm text-gray-500">{{ $user->email }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if ($user->roles->isNotEmpty())
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    {{ $user->roles->first()->name }}
                                </span>
                        @else
                            <span class="text-sm text-gray-500">Sin rol</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $user->store?->name ?? 'Sin asignar' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if ($user->is_active)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    Activo
                                </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    Inactivo
                                </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <div class="flex justify-end gap-2">
                            <button wire:click="openEditModal({{ $user->id }})"
                                    class="text-blue-600 hover:text-blue-900">
                                Editar
                            </button>
                            <button wire:click="toggleUserStatus({{ $user->id }})"
                                    class="text-{{ $user->is_active ? 'orange' : 'green' }}-600 hover:text-{{ $user->is_active ? 'orange' : 'green' }}-900">
                                {{ $user->is_active ? 'Desactivar' : 'Activar' }}
                            </button>
                            <button wire:click="openPasswordModal({{ $user->id }})"
                                    class="text-purple-600 hover:text-purple-900">
                                Resetear
                            </button>
                            <button wire:click="confirmDelete({{ $user->id }})"
                                    class="text-red-600 hover:text-red-900">
                                Eliminar
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                        No se encontraron usuarios.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>

        <!-- Pagination -->
        <div class="px-6 py-3 border-t border-gray-200">
            {{ $users->links() }}
        </div>
    </div>

    <!-- Create/Edit Modal -->
    @if ($showModal)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-10 mx-auto p-5 border w-11/12 md:w-4/5 lg:w-3/4 shadow-lg rounded-md bg-white max-h-[90vh] overflow-y-auto">
                <div class="mb-4">
                    <h3 class="text-lg font-medium text-gray-900">
                        {{ $editingUser ? 'Editar Usuario' : 'Crear Nuevo Usuario' }}
                    </h3>
                </div>

                <form wire:submit="save">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Left Column -->
                        <div class="space-y-4">
                            <!-- ID Usuario (read-only for editing) -->
                            @if ($editingUser)
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">ID Usuario</label>
                                    <input type="text" value="{{ $editingUser->formatted_id }}" readonly
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100">
                                </div>
                            @endif

                            <!-- Name -->
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                                <input wire:model="name" type="text" id="name"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <!-- Email -->
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Correo Electrónico *</label>
                                <input wire:model="email" type="email" id="email"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                @error('email') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <!-- Phone -->
                            <div>
                                <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Número de Teléfono</label>
                                <input wire:model="phone" type="text" id="phone"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                @error('phone') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <!-- Mobile -->
                            <div>
                                <label for="mobile" class="block text-sm font-medium text-gray-700 mb-1">Número de Celular</label>
                                <input wire:model="mobile" type="text" id="mobile"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                @error('mobile') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <!-- Role -->
                            <div>
                                <label for="selectedRole" class="block text-sm font-medium text-gray-700 mb-1">Rol de Usuario</label>
                                <select wire:model="selectedRole" id="selectedRole"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">Seleccionar rol</option>
                                    @foreach ($roles as $role)
                                        <option value="{{ $role->name }}">{{ $role->name }}</option>
                                    @endforeach
                                </select>
                                @error('selectedRole') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <!-- Middle Column -->
                        <div class="space-y-4">
                            <!-- Store -->
                            <div>
                                <label for="store_id" class="block text-sm font-medium text-gray-700 mb-1">Asignar a la tienda</label>
                                <select wire:model="store_id" id="store_id"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">Seleccionar tienda</option>
                                    @foreach ($stores as $store)
                                        <option value="{{ $store->id }}">{{ $store->name }}</option>
                                    @endforeach
                                </select>
                                @error('store_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <!-- Address -->
                            <div>
                                <label for="address" class="block text-sm font-medium text-gray-700 mb-1">Dirección</label>
                                <input wire:model="address" type="text" id="address"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                @error('address') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <!-- Address 2 -->
                            <div>
                                <label for="address_2" class="block text-sm font-medium text-gray-700 mb-1">Dirección 2</label>
                                <input wire:model="address_2" type="text" id="address_2"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                @error('address_2') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <!-- City -->
                            <div>
                                <label for="city" class="block text-sm font-medium text-gray-700 mb-1">Ciudad</label>
                                <input wire:model="city" type="text" id="city"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                @error('city') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <!-- State -->
                            <div>
                                <label for="state" class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                                <input wire:model="state" type="text" id="state"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                @error('state') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="space-y-4">
                            <!-- Country -->
                            <div>
                                <label for="country" class="block text-sm font-medium text-gray-700 mb-1">País</label>
                                <select wire:model="country" id="country"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">Seleccionar país</option>
                                    @foreach ($countries as $code => $name)
                                        <option value="{{ $code }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                                @error('country') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <!-- Postal Code -->
                            <div>
                                <label for="postal_code" class="block text-sm font-medium text-gray-700 mb-1">Código Postal</label>
                                <input wire:model="postal_code" type="text" id="postal_code"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                @error('postal_code') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <!-- Password Section -->
                            <div class="border-t pt-4">
                                <h4 class="font-medium text-gray-900 mb-3">
                                    {{ $editingUser ? 'Cambiar Contraseña (opcional)' : 'Contraseña *' }}
                                </h4>

                                <!-- Password -->
                                <div class="mb-4">
                                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                                        {{ $editingUser ? 'Nueva Clave' : 'Nueva Clave *' }}
                                    </label>
                                    <input wire:model="password" type="password" id="password"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    @error('password') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>

                                <!-- Confirm Password -->
                                <div>
                                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">
                                        {{ $editingUser ? 'Confirmar Nueva Clave' : 'Confirmar Nueva Clave *' }}
                                    </label>
                                    <input wire:model="password_confirmation" type="password" id="password_confirmation"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                            </div>

                            <!-- Status -->
                            <div class="flex items-center">
                                <input wire:model="is_active" type="checkbox" id="is_active"
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                <label for="is_active" class="ml-2 text-sm text-gray-700">Usuario activo</label>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end gap-3 mt-6 pt-4 border-t">
                        <button type="button" wire:click="closeModal"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">
                            Cancelar
                        </button>
                        <button type="submit"
                                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">
                            {{ $editingUser ? 'Actualizar' : 'Crear' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Delete Confirmation Modal -->
    @if ($showDeleteModal && $userToDelete)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3 text-center">
                    <h3 class="text-lg font-medium text-gray-900">Confirmar Eliminación</h3>
                    <div class="mt-2 px-7 py-3">
                        <p class="text-sm text-gray-500">
                            ¿Estás seguro de que deseas desactivar al usuario "{{ $userToDelete->name }}"?
                            Esta acción se puede revertir activando el usuario nuevamente.
                        </p>
                    </div>
                    <div class="items-center px-4 py-3">
                        <button wire:click="delete"
                                class="px-4 py-2 bg-red-500 text-white text-base font-medium rounded-md shadow-sm hover:bg-red-700 mr-2">
                            Desactivar
                        </button>
                        <button wire:click="$set('showDeleteModal', false)"
                                class="px-4 py-2 bg-gray-500 text-white text-base font-medium rounded-md shadow-sm hover:bg-gray-700">
                            Cancelar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Password Reset Modal -->
    @if ($showPasswordModal && $userToResetPassword)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Resetear Contraseña</h3>
                    <p class="text-sm text-gray-600">Usuario: {{ $userToResetPassword->name }}</p>
                </div>

                <form wire:submit="resetPassword">
                    <div class="mb-4">
                        <label for="newPassword" class="block text-sm font-medium text-gray-700 mb-1">Nueva Contraseña *</label>
                        <input wire:model="newPassword" type="password" id="newPassword"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @error('newPassword') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-6">
                        <label for="newPassword_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Confirmar Contraseña *</label>
                        <input wire:model="newPassword_confirmation" type="password" id="newPassword_confirmation"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div class="flex justify-end gap-3">
                        <button type="button" wire:click="$set('showPasswordModal', false)"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">
                            Cancelar
                        </button>
                        <button type="submit"
                                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">
                            Actualizar Contraseña
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
