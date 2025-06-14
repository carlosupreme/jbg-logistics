<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Validate;
use Livewire\WithPagination;
use App\Models\Store;
use App\Models\Zone;
use App\Utils\Countries;

new class extends Component {
    use WithPagination;

    // Component state

    public $zones = [];
    public $countries = [];
    public $showModal = false;
    public $showViewModal = false;
    public $editingStore = null;
    public $viewingStore = null;
    public $isEditing = false;

    // Search and filters
    public $search = '';
    public $countryFilter = '';
    public $zoneFilter = '';
    public $sortBy = 'name';
    public $sortDirection = 'asc';

    // Form fields
    #[Validate('nullable|string')]
    public $office_number = '';

    #[Validate('required|string')]
    public $name = '';

    #[Validate('nullable|string')]
    public $supervisor = '';

    #[Validate('nullable|email')]
    public $email = '';

    #[Validate('nullable|string')]
    public $phone = '';

    #[Validate('nullable|string')]
    public $mobile = '';

    #[Validate('nullable|string')]
    public $address = '';

    #[Validate('nullable|string')]
    public $address_2 = '';

    #[Validate('nullable|string')]
    public $city = '';

    #[Validate('nullable|string')]
    public $state = '';

    #[Validate('required|string')]
    public $country = '';

    #[Validate('nullable|string')]
    public $postal_code = '';

    #[Validate('required|exists:zones,id')]
    public $zone_id = '';

    public function with(): array
    {
        return [
            'stores' => $this->getFilteredStores(),
        ];
    }
    // Lifecycle methods
    public function mount()
    {
        $this->zones = Zone::orderBy('name')->get();
        $this->countries = Countries::all();
    }

    // Get filtered stores query
    public function getFilteredStores()
    {
        $query = Store::with('zone');

        // Apply search filter
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('supervisor', 'like', '%' . $this->search . '%')
                    ->orWhere('email', 'like', '%' . $this->search . '%')
                    ->orWhere('city', 'like', '%' . $this->search . '%')
                    ->orWhere('address', 'like', '%' . $this->search . '%')
                    ->orWhere('office_number', 'like', '%' . $this->search . '%');
            });
        }

        // Apply country filter
        if ($this->countryFilter) {
            $query->where('country', $this->countryFilter);
        }

        // Apply zone filter
        if ($this->zoneFilter) {
            $query->where('zone_id', $this->zoneFilter);
        }

        // Apply sorting
        $query->orderBy($this->sortBy, $this->sortDirection);

        return $query->paginate(10);
    }

    // Open modal for creating new store
    public function create()
    {
        $this->resetForm();
        $this->isEditing = false;
        $this->showModal = true;
    }

    // Open modal for editing existing store
    public function edit($storeId)
    {
        $this->editingStore = Store::findOrFail($storeId);
        $this->fillForm($this->editingStore);
        $this->isEditing = true;
        $this->showModal = true;
    }

    // Show store details in modal
    public function view($storeId)
    {
        $this->viewingStore = Store::with('zone')->findOrFail($storeId);
        $this->showViewModal = true;
    }

    // Save store (create or update)
    public function save()
    {
        $this->validate();

        try {
            if ($this->isEditing) {
                // Update existing store
                $this->editingStore->update([
                    'office_number' => $this->office_number,
                    'name' => $this->name,
                    'supervisor' => $this->supervisor,
                    'email' => $this->email,
                    'phone' => $this->phone,
                    'mobile' => $this->mobile,
                    'address' => $this->address,
                    'address_2' => $this->address_2,
                    'city' => $this->city,
                    'state' => $this->state,
                    'country' => $this->country,
                    'postal_code' => $this->postal_code,
                    'zone_id' => $this->zone_id,
                ]);
                session()->flash('message', '¡Tienda actualizada exitosamente!');
            } else {
                // Create new store
                Store::create([
                    'office_number' => $this->office_number,
                    'name' => $this->name,
                    'supervisor' => $this->supervisor,
                    'email' => $this->email,
                    'phone' => $this->phone,
                    'mobile' => $this->mobile,
                    'address' => $this->address,
                    'address_2' => $this->address_2,
                    'city' => $this->city,
                    'state' => $this->state,
                    'country' => $this->country,
                    'postal_code' => $this->postal_code,
                    'zone_id' => $this->zone_id,
                ]);
                session()->flash('message', '¡Tienda creada exitosamente!');
            }

            $this->closeModal();
            $this->resetPage();
        } catch (\Exception $e) {
            session()->flash('error', 'Ocurrió un error: ' . $e->getMessage());
        }
    }

    // Delete store
    public function delete($storeId)
    {
        try {
            $store = Store::findOrFail($storeId);
            $storeName = $store->name;
            $store->delete();

            session()->flash('message', "¡Tienda '{$storeName}' eliminada exitosamente!");
            $this->resetPage();
        } catch (\Exception $e) {
            session()->flash('error', 'Ocurrió un error al eliminar: ' . $e->getMessage());
        }
    }

    // Close modals and reset form
    public function closeModal()
    {
        $this->showModal = false;
        $this->showViewModal = false;
        $this->resetForm();
    }

    // Reset form fields
    public function resetForm()
    {
        $this->office_number = '';
        $this->name = '';
        $this->supervisor = '';
        $this->email = '';
        $this->phone = '';
        $this->mobile = '';
        $this->address = '';
        $this->address_2 = '';
        $this->city = '';
        $this->state = '';
        $this->country = '';
        $this->postal_code = '';
        $this->zone_id = '';
        $this->editingStore = null;
        $this->resetValidation();
    }

    // Fill form with store data
    public function fillForm($store)
    {
        $this->office_number = $store->office_number ?? '';
        $this->name = $store->name;
        $this->supervisor = $store->supervisor ?? '';
        $this->email = $store->email ?? '';
        $this->phone = $store->phone ?? '';
        $this->mobile = $store->mobile ?? '';
        $this->address = $store->address ?? '';
        $this->address_2 = $store->address_2 ?? '';
        $this->city = $store->city ?? '';
        $this->state = $store->state ?? '';
        $this->country = $store->country;
        $this->postal_code = $store->postal_code ?? '';
        $this->zone_id = (string) $store->zone_id;
    }

    // Sorting functionality
    public function sortBy($field)
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }

    // Search and filter updates
    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedCountryFilter()
    {
        $this->resetPage();
    }

    public function updatedZoneFilter()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->countryFilter = '';
        $this->zoneFilter = '';
        $this->resetPage();
    }

    // Define which properties should be included in the query string for pagination
    protected function getQueryString()
    {
        return [
            'search' => ['except' => ''],
            'countryFilter' => ['except' => ''],
            'zoneFilter' => ['except' => ''],
            'sortBy' => ['except' => 'name'],
            'sortDirection' => ['except' => 'asc'],
        ];
    }
}; ?>

<div class="p-6" x-data="{}">
    <!-- Page Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">Gestión de Tiendas</h1>
            <p class="text-gray-600 dark:text-gray-400">Administra tus tiendas aquí</p>
        </div>

        <button wire:click="create"
                class="bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600
                       text-white px-4 py-2 rounded-lg flex items-center gap-2 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            Agregar Nueva Tienda
        </button>
    </div>

    <!-- Flash Messages -->
    @if (session()->has('message'))
        <div class="mb-4 bg-green-50 dark:bg-green-900/50 border border-green-200 dark:border-green-800
                    text-green-700 dark:text-green-300 px-4 py-3 rounded-lg flex items-start gap-3">
            <svg class="w-5 h-5 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <span>{{ session('message') }}</span>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="mb-4 bg-red-50 dark:bg-red-900/50 border border-red-200 dark:border-red-800
                    text-red-700 dark:text-red-300 px-4 py-3 rounded-lg flex items-start gap-3">
            <svg class="w-5 h-5 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 shadow-sm border border-gray-200 dark:border-gray-700 rounded-lg p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Buscar</label>
                <div class="relative">
                    <input wire:model.live.debounce.300ms="search"
                           type="text"
                           placeholder="Buscar tiendas..."
                           class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg
                                  focus:ring-2 focus:ring-blue-500 focus:border-transparent
                                  bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100
                                  placeholder-gray-500 dark:placeholder-gray-400">
                    <svg class="absolute left-3 top-2.5 h-5 w-5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">País</label>
                <select wire:model.live="countryFilter"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg
                               focus:ring-2 focus:ring-blue-500 focus:border-transparent
                               bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100">
                    <option value="">Todos los Países</option>
                    @foreach($countries as $code => $name)
                        <option value="{{ $code }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Zona</label>
                <select wire:model.live="zoneFilter"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg
                               focus:ring-2 focus:ring-blue-500 focus:border-transparent
                               bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100">
                    <option value="">Todas las Zonas</option>
                    @foreach($zones as $zone)
                        <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end">
                <button wire:click="clearFilters"
                        class="w-full px-4 py-2 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700
                               hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors">
                    Limpiar Filtros
                </button>
            </div>
        </div>
    </div>

    <!-- Stores Table -->
    <div class="bg-white dark:bg-gray-800 shadow-sm border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700/50 transition-colors"
                        wire:click="sortBy('office_number')">
                        Oficina #
                        @if($sortBy === 'office_number')
                            <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                        @endif
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700/50 transition-colors"
                        wire:click="sortBy('name')">
                        Nombre
                        @if($sortBy === 'name')
                            <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                        @endif
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Supervisor
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Contacto
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Ubicación
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Zona
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Acciones
                    </th>
                </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($stores as $store)
                    <tr wire:key="store-{{ $store->id }}" class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                            {{ $store->office_number ?: '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $store->name }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            {{ $store->supervisor ?: '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            @if($store->email)
                                <div>{{ $store->email }}</div>
                            @endif
                            @if($store->phone)
                                <div>{{ $store->phone }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            @if($store->city)
                                {{ $store->city }},
                            @endif
                            {{ $countries[$store->country] ?? $store->country }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                         bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-200">
                                {{ $store->zone->name }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex space-x-2">
                                <button wire:click="view({{ $store->id }})"
                                        class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300
                                               flex items-center gap-1 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>

                                </button>

                                <button wire:click="edit({{ $store->id }})"
                                        class="text-yellow-600 dark:text-yellow-400 hover:text-yellow-900 dark:hover:text-yellow-300
                                               flex items-center gap-1 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>

                                </button>

                                <button wire:click="delete({{ $store->id }})"
                                        wire:confirm="¿Estás seguro de que deseas eliminar esta tienda?"
                                        class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300
                                               flex items-center gap-1 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>

                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center">
                                <svg class="w-12 h-12 mb-4 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                                <p class="text-gray-500 dark:text-gray-400 text-lg mb-1">No se encontraron tiendas</p>
                                <p class="text-gray-400 dark:text-gray-500 text-sm">Crea tu primera tienda para comenzar</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if($stores->hasPages())
            <div class="px-6 py-3 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
                {{ $stores->links() }}
            </div>
        @endif
    </div>

    <!-- Create/Edit Modal -->
    @if ($showModal)
        <div class="fixed inset-0 bg-gray-600/50 dark:bg-gray-900/75 overflow-y-auto h-full w-full z-50"
             x-data x-show="true" x-transition>
            <div class="relative top-10 mx-auto p-5 border max-w-4xl shadow-lg rounded-lg
                        bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700">
                <form wire:submit.prevent="save">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            {{ $isEditing ? 'Editar Tienda' : 'Crear Nueva Tienda' }}
                        </h2>
                        <button type="button" wire:click="closeModal"
                                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <div class="space-y-6 max-h-96 overflow-y-auto">
                        <!-- Basic Information -->
                        <div>
                            <h3 class="text-md font-medium text-gray-900 dark:text-gray-100 mb-4">Información Básica</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Número de Oficina
                                    </label>
                                    <input wire:model="office_number"
                                           type="text"
                                           placeholder="Ingresa el número de oficina"
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg
                                                  focus:ring-2 focus:ring-blue-500 focus:border-transparent
                                                  bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100
                                                  placeholder-gray-500 dark:placeholder-gray-400">
                                    @error('office_number')
                                        <span class="text-red-500 dark:text-red-400 text-sm">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Nombre de la Tienda <span class="text-red-500">*</span>
                                    </label>
                                    <input wire:model="name"
                                           type="text"
                                           placeholder="Ingresa el nombre de la tienda"
                                           required
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg
                                                  focus:ring-2 focus:ring-blue-500 focus:border-transparent
                                                  bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100
                                                  placeholder-gray-500 dark:placeholder-gray-400">
                                    @error('name')
                                        <span class="text-red-500 dark:text-red-400 text-sm">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Supervisor
                                    </label>
                                    <input wire:model="supervisor"
                                           type="text"
                                           placeholder="Ingresa el nombre del supervisor"
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg
                                                  focus:ring-2 focus:ring-blue-500 focus:border-transparent
                                                  bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100
                                                  placeholder-gray-500 dark:placeholder-gray-400">
                                    @error('supervisor')
                                        <span class="text-red-500 dark:text-red-400 text-sm">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Zona <span class="text-red-500">*</span>
                                    </label>
                                    <select wire:model="zone_id"
                                            required
                                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg
                                                   focus:ring-2 focus:ring-blue-500 focus:border-transparent
                                                   bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100">
                                        <option value="">Selecciona una zona</option>
                                        @foreach($zones as $zone)
                                            <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('zone_id')
                                        <span class="text-red-500 dark:text-red-400 text-sm">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Contact Information -->
                        <div>
                            <h3 class="text-md font-medium text-gray-900 dark:text-gray-100 mb-4">Información de Contacto</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Correo Electrónico
                                    </label>
                                    <input wire:model="email"
                                           type="email"
                                           placeholder="Ingresa el correo electrónico"
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg
                                                  focus:ring-2 focus:ring-blue-500 focus:border-transparent
                                                  bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100
                                                  placeholder-gray-500 dark:placeholder-gray-400">
                                    @error('email')
                                        <span class="text-red-500 dark:text-red-400 text-sm">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Teléfono
                                    </label>
                                    <input wire:model="phone"
                                           type="text"
                                           placeholder="Ingresa el número de teléfono"
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg
                                                  focus:ring-2 focus:ring-blue-500 focus:border-transparent
                                                  bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100
                                                  placeholder-gray-500 dark:placeholder-gray-400">
                                    @error('phone')
                                        <span class="text-red-500 dark:text-red-400 text-sm">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Móvil
                                    </label>
                                    <input wire:model="mobile"
                                           type="text"
                                           placeholder="Ingresa el número de móvil"
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg
                                                  focus:ring-2 focus:ring-blue-500 focus:border-transparent
                                                  bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100
                                                  placeholder-gray-500 dark:placeholder-gray-400">
                                    @error('mobile')
                                        <span class="text-red-500 dark:text-red-400 text-sm">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Address Information -->
                        <div>
                            <h3 class="text-md font-medium text-gray-900 dark:text-gray-100 mb-4">Información de Dirección</h3>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Dirección Línea 1
                                    </label>
                                    <input wire:model="address"
                                           type="text"
                                           placeholder="Ingresa la dirección principal"
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg
                                                  focus:ring-2 focus:ring-blue-500 focus:border-transparent
                                                  bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100
                                                  placeholder-gray-500 dark:placeholder-gray-400">
                                    @error('address')
                                        <span class="text-red-500 dark:text-red-400 text-sm">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Dirección Línea 2
                                    </label>
                                    <input wire:model="address_2"
                                           type="text"
                                           placeholder="Ingresa dirección secundaria (opcional)"
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg
                                                  focus:ring-2 focus:ring-blue-500 focus:border-transparent
                                                  bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100
                                                  placeholder-gray-500 dark:placeholder-gray-400">
                                    @error('address_2')
                                        <span class="text-red-500 dark:text-red-400 text-sm">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Ciudad
                                        </label>
                                        <input wire:model="city"
                                               type="text"
                                               placeholder="Ingresa la ciudad"
                                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg
                                                      focus:ring-2 focus:ring-blue-500 focus:border-transparent
                                                      bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100
                                                      placeholder-gray-500 dark:placeholder-gray-400">
                                        @error('city')
                                            <span class="text-red-500 dark:text-red-400 text-sm">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Estado/Provincia
                                        </label>
                                        <input wire:model="state"
                                               type="text"
                                               placeholder="Ingresa el estado o provincia"
                                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg
                                                      focus:ring-2 focus:ring-blue-500 focus:border-transparent
                                                      bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100
                                                      placeholder-gray-500 dark:placeholder-gray-400">
                                        @error('state')
                                            <span class="text-red-500 dark:text-red-400 text-sm">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Código Postal
                                        </label>
                                        <input wire:model="postal_code"
                                               type="text"
                                               placeholder="Ingresa el código postal"
                                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg
                                                      focus:ring-2 focus:ring-blue-500 focus:border-transparent
                                                      bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100
                                                      placeholder-gray-500 dark:placeholder-gray-400">
                                        @error('postal_code')
                                            <span class="text-red-500 dark:text-red-400 text-sm">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        País <span class="text-red-500">*</span>
                                    </label>
                                    <select wire:model="country"
                                            required
                                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg
                                                   focus:ring-2 focus:ring-blue-500 focus:border-transparent
                                                   bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100">
                                        <option value="">Selecciona un país</option>
                                        @foreach($countries as $code => $name)
                                            <option value="{{ $code }}">{{ $name }}</option>
                                        @endforeach
                                    </select>
                                    @error('country')
                                        <span class="text-red-500 dark:text-red-400 text-sm">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Actions -->
                    <div class="flex justify-end space-x-3 mt-6 pt-4 border-t border-gray-200 dark:border-gray-600">
                        <button type="button"
                                wire:click="closeModal"
                                class="px-4 py-2 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700
                                       hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors">
                            Cancelar
                        </button>

                        <button type="submit"
                                wire:loading.attr="disabled"
                                class="px-4 py-2 text-white bg-blue-600 dark:bg-blue-500
                                       hover:bg-blue-700 dark:hover:bg-blue-600 rounded-lg transition-colors
                                       disabled:opacity-50 disabled:cursor-not-allowed">
                            <span wire:loading.remove>
                                {{ $isEditing ? 'Actualizar Tienda' : 'Crear Tienda' }}
                            </span>
                            <span wire:loading class="flex items-center gap-2">
                                <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                {{ $isEditing ? 'Actualizando...' : 'Creando...' }}
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- View Modal -->
    @if ($showViewModal && $viewingStore)
        <div class="fixed inset-0 bg-gray-600/50 dark:bg-gray-900/75 overflow-y-auto h-full w-full z-50"
             x-data x-show="true" x-transition>
            <div class="relative top-10 mx-auto p-5 border max-w-4xl shadow-lg rounded-lg
                        bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700">
                <div class="mb-6 flex justify-between items-center">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $viewingStore->name }}</h2>
                        <p class="text-gray-600 dark:text-gray-400">Detalles de la Tienda</p>
                    </div>
                    <button type="button" wire:click="closeModal"
                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="space-y-6 max-h-96 overflow-y-auto">
                    <!-- Basic Information -->
                    <div>
                        <h3 class="text-md font-medium text-gray-900 dark:text-gray-100 mb-4">Información Básica</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Número de Oficina</label>
                                <div class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $viewingStore->office_number ?: 'No especificado' }}</div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nombre de la Tienda</label>
                                <div class="mt-1 text-sm font-medium text-gray-900 dark:text-gray-100">{{ $viewingStore->name }}</div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Supervisor</label>
                                <div class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $viewingStore->supervisor ?: 'No asignado' }}</div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Zona</label>
                                <div class="mt-1">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                 bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-200">
                                        {{ $viewingStore->zone->name }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Information -->
                    <div>
                        <h3 class="text-md font-medium text-gray-900 dark:text-gray-100 mb-4">Información de Contacto</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Correo Electrónico</label>
                                <div class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                    @if($viewingStore->email)
                                        <a href="mailto:{{ $viewingStore->email }}" class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">
                                            {{ $viewingStore->email }}
                                        </a>
                                    @else
                                        No proporcionado
                                    @endif
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Teléfono</label>
                                <div class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                    @if($viewingStore->phone)
                                        <a href="tel:{{ $viewingStore->phone }}" class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">
                                            {{ $viewingStore->phone }}
                                        </a>
                                    @else
                                        No proporcionado
                                    @endif
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Móvil</label>
                                <div class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                    @if($viewingStore->mobile)
                                        <a href="tel:{{ $viewingStore->mobile }}" class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">
                                            {{ $viewingStore->mobile }}
                                        </a>
                                    @else
                                        No proporcionado
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Address Information -->
                    <div>
                        <h3 class="text-md font-medium text-gray-900 dark:text-gray-100 mb-4">Información de Dirección</h3>
                        @if($viewingStore->full_address !== ', ')
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Dirección Completa</label>
                                <div class="mt-1 text-sm text-gray-900 dark:text-gray-100 leading-relaxed">
                                    {{ $viewingStore->full_address }}
                                </div>
                            </div>
                        @endif
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">País</label>
                                <div class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $viewingStore->country?: 'No especificado' }}</div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Ciudad</label>
                                <div class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $viewingStore->city ?: 'No especificado' }}</div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Estado/Provincia</label>
                                <div class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $viewingStore->state ?: 'No especificado' }}</div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Código Postal</label>
                                <div class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $viewingStore->postal_code ?: 'No especificado' }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Metadata -->
                    <div>
                        <h3 class="text-md font-medium text-gray-900 dark:text-gray-100 mb-4">Metadatos</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Creado</label>
                                <div class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $viewingStore->created_at->format('j M Y g:i A') }}</div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Última Actualización</label>
                                <div class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $viewingStore->updated_at->format('j M Y g:i A') }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Actions -->
                <div class="flex justify-end space-x-3 mt-6 pt-4 border-t border-gray-200 dark:border-gray-600">
                    <button type="button"
                            wire:click="closeModal"
                            class="px-4 py-2 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700
                                   hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors">
                        Cerrar
                    </button>
                    <button wire:click="edit({{ $viewingStore->id }})"
                            class="px-4 py-2 text-white bg-blue-600 dark:bg-blue-500
                                   hover:bg-blue-700 dark:hover:bg-blue-600 rounded-lg transition-colors">
                        Editar Tienda
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
