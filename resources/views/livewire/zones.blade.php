<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Validate;
use App\Models\Zone;

new class extends Component {
    // Component state
    public $zones = [];
    public $showModal = false;
    public $editingZone = null;
    public $isEditing = false;

    // Form fields
    #[Validate('required|string|max:255')]
    public $name = '';

    // Lifecycle methods
    public function mount()
    {
        $this->loadZones();
    }

    // Load zones from database
    public function loadZones()
    {
        $this->zones = Zone::orderBy('name')->get();
    }

    // Open modal for creating new zone
    public function create()
    {
        $this->resetForm();
        $this->isEditing = false;
        $this->showModal = true;
    }

    // Open modal for editing existing zone
    public function edit($zoneId)
    {
        $this->editingZone = Zone::findOrFail($zoneId);
        $this->name = $this->editingZone->name;
        $this->isEditing = true;
        $this->showModal = true;
    }

    // Save zone (create or update)
    public function save()
    {
        // Dynamic validation for unique constraint
        $rules = [
            'name' => 'required|string|max:255|unique:zones,name'
        ];

        // If editing, exclude current zone from unique validation
        if ($this->isEditing && $this->editingZone) {
            $rules['name'] .= ',' . $this->editingZone->id;
        }

        $this->validate($rules);

        try {
            if ($this->isEditing) {
                // Update existing zone
                $this->editingZone->update([
                    'name' => $this->name
                ]);
                session()->flash('message', 'Zone updated successfully!');
            } else {
                // Create new zone
                Zone::create([
                    'name' => $this->name
                ]);
                session()->flash('message', 'Zone created successfully!');
            }

            $this->closeModal();
            $this->loadZones();
        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: ' . $e->getMessage());
        }
    }

    // Delete zone
    public function delete($zoneId)
    {
        try {
            $zone = Zone::findOrFail($zoneId);
            $zone->delete();

            session()->flash('message', 'Zone deleted successfully!');
            $this->loadZones();
        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred while deleting: ' . $e->getMessage());
        }
    }

    // Close modal and reset form
    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    // Reset form fields
    public function resetForm()
    {
        $this->name = '';
        $this->editingZone = null;
        $this->resetValidation();
    }
}; ?>

<div class="p-6" x-data="{}">
    <!-- Page Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">Zones Management</h1>
            <p class="text-gray-600 dark:text-gray-400">Manage your zones here</p>
        </div>

        <button wire:click="create"
                class="bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600
                       text-white px-4 py-2 rounded-lg flex items-center gap-2 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            Add New Zone
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

    <!-- Zones Table -->
    <div class="bg-white dark:bg-gray-800 shadow-sm border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        ID
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Name
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Created At
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Actions
                    </th>
                </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($zones as $zone)
                    <tr wire:key="zone-{{ $zone->id }}" class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                            {{ $zone->id }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $zone->name }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            {{ $zone->created_at->format('M d, Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex space-x-2">
                                <button wire:click="edit({{ $zone->id }})"
                                        class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300
                                               flex items-center gap-1 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>

                                </button>

                                <button wire:click="delete({{ $zone->id }})"
                                        wire:confirm="Are you sure you want to delete this zone?"
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
                        <td colspan="4" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center">
                                <svg class="w-12 h-12 mb-4 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                </svg>
                                <p class="text-gray-500 dark:text-gray-400 text-lg mb-1">No zones found</p>
                                <p class="text-gray-400 dark:text-gray-500 text-sm">Create your first zone to get started</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal for Create/Edit -->
    @if ($showModal)
        <div class="fixed inset-0 bg-gray-600/50 dark:bg-gray-900/75 overflow-y-auto h-full w-full z-50"
             x-data x-show="true" x-transition>
            <div class="relative top-20 mx-auto p-5 border max-w-md shadow-lg rounded-lg
                        bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700">
                <form wire:submit.prevent="save">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            {{ $isEditing ? 'Edit Zone' : 'Create New Zone' }}
                        </h2>
                        <button type="button" wire:click="closeModal"
                                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <div class="space-y-4">
                        <!-- Zone Name Field -->
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Zone Name
                            </label>
                            <input wire:model="name"
                                   type="text"
                                   id="name"
                                   placeholder="Enter zone name"
                                   required
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg
                                          focus:ring-2 focus:ring-blue-500 focus:border-transparent
                                          bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100
                                          placeholder-gray-500 dark:placeholder-gray-400">
                            @error('name')
                                <span class="text-red-500 dark:text-red-400 text-sm">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- Modal Actions -->
                    <div class="flex justify-end space-x-3 mt-6 pt-4 border-t border-gray-200 dark:border-gray-600">
                        <button type="button"
                                wire:click="closeModal"
                                class="px-4 py-2 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700
                                       hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors">
                            Cancel
                        </button>

                        <button type="submit"
                                wire:loading.attr="disabled"
                                class="px-4 py-2 text-white bg-blue-600 dark:bg-blue-500
                                       hover:bg-blue-700 dark:hover:bg-blue-600 rounded-lg transition-colors
                                       disabled:opacity-50 disabled:cursor-not-allowed">
                            <span wire:loading.remove>
                                {{ $isEditing ? 'Update Zone' : 'Create Zone' }}
                            </span>
                            <span wire:loading class="flex items-center gap-2">
                                <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                {{ $isEditing ? 'Updating...' : 'Creating...' }}
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
