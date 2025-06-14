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

<div class="p-6">
    <!-- Page Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Zones Management</h1>
            <p class="text-gray-600">Manage your zones here</p>
        </div>

        <flux:button variant="primary" wire:click="create" icon="plus">
            Add New Zone
        </flux:button>
    </div>

    <!-- Flash Messages -->
    @if (session()->has('message'))
        <flux:callout variant="success" class="mb-4">
            {{ session('message') }}
        </flux:callout>
    @endif

    @if (session()->has('error'))
        <flux:callout variant="danger" class="mb-4">
            {{ session('error') }}
        </flux:callout>
    @endif

    <!-- Custom Card Component -->
    <div class="bg-white shadow-sm border border-gray-200 rounded-lg overflow-hidden">
        <!-- Custom Table Component -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        ID
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Name
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Created At
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Actions
                    </th>
                </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                @forelse($zones as $zone)
                    <tr wire:key="zone-{{ $zone->id }}" class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $zone->id }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm font-medium text-gray-900">{{ $zone->name }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $zone->created_at->format('M d, Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex space-x-2">
                                <flux:button
                                    variant="subtle"
                                    size="sm"
                                    wire:click="edit({{ $zone->id }})"
                                    icon="pencil">
                                    Edit
                                </flux:button>

                                <flux:button
                                    variant="danger"
                                    size="sm"
                                    wire:click="delete({{ $zone->id }})"
                                    wire:confirm="Are you sure you want to delete this zone?"
                                    icon="trash">
                                    Delete
                                </flux:button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center">
                                <svg class="w-12 h-12 mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                </svg>
                                <p class="text-gray-500 text-lg mb-1">No zones found</p>
                                <p class="text-gray-400 text-sm">Create your first zone to get started</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal for Create/Edit -->
    <flux:modal wire:model="showModal" class="md:w-96">
        <form wire:submit.prevent="save">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-lg font-semibold">
                    {{ $isEditing ? 'Edit Zone' : 'Create New Zone' }}
                </h2>
             </div>

            <div class="space-y-4">
                <!-- Zone Name Field -->
                <flux:field>
                    <flux:label>Zone Name</flux:label>
                    <flux:input
                        wire:model="name"
                        placeholder="Enter zone name"
                        required
                    />
                    <flux:error name="name" />
                </flux:field>
            </div>

            <!-- Modal Actions -->
            <div class="flex justify-end space-x-3 mt-6">
                <flux:button variant="ghost" wire:click="closeModal">
                    Cancel
                </flux:button>

                <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                    <span wire:loading.remove>
                        {{ $isEditing ? 'Update Zone' : 'Create Zone' }}
                    </span>
                    <span wire:loading>
                        {{ $isEditing ? 'Updating...' : 'Creating...' }}
                    </span>
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>
