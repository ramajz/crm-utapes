<?php

namespace App\Livewire;

use App\Models\Lead;
use App\Services\LeadService;
use Livewire\Component;

class UpdateLeadStatus extends Component
{
    public Lead $lead;
    public string $statusFu = '';
    public ?string $notes = '';
    public ?string $size = '';

    // WA Callback mode
    public bool $waCallback = false;

    // Computed properties
    public string $funnelStage;
    public string $funnelLabel;

    protected LeadService $leadService;

    protected $listeners = [
        'openUpdateModal' => 'loadLead',
        'showWaNotesModal' => 'triggerWaCallback',
    ];

    public function boot(LeadService $leadService)
    {
        $this->leadService = $leadService;
    }

    public function loadLead(int $leadId): void
    {
        $this->lead = Lead::with(['customer', 'handler'])->findOrFail($leadId);
        $this->authorizeAccess();
        $this->statusFu = $this->lead->status_fu;
        $this->notes = $this->lead->notes;
        $this->size = $this->lead->size ?? '';
        $this->waCallback = false;
        $this->updateFunnel();
    }

    /**
     * Triggered when user returns from WhatsApp chat.
     * Sets WA callback mode to show the notes-focused modal.
     */
    public function triggerWaCallback(int $leadId): void
    {
        if (!isset($this->lead) || $this->lead->id !== $leadId) {
            $this->lead = Lead::with(['customer', 'handler'])->findOrFail($leadId);
        }
        $this->authorizeAccess();

        $this->statusFu = $this->lead->status_fu;
        $this->notes = $this->lead->notes;
        $this->size = $this->lead->size ?? '';
        $this->waCallback = true;
        $this->updateFunnel();
    }

    public function clearWaCallback(): void
    {
        $this->waCallback = false;
    }

    public function updatedStatusFu(): void
    {
        $this->updateFunnel();
    }

    public function updateFunnel(): void
    {
        $this->funnelStage = Lead::mapStatusToFunnel($this->statusFu);
        $this->funnelLabel = ucfirst($this->funnelStage);
    }

    public function save(): void
    {
        $this->validate([
            'statusFu' => 'required|string|in:' . implode(',', Lead::STATUSES),
            'notes' => 'nullable|string',
            'size' => 'nullable|string|max:5',
        ]);

        $this->lead = $this->leadService->updateStatus(
            $this->lead,
            [
                'status_fu' => $this->statusFu,
                'notes' => $this->notes,
                'size' => $this->size,
            ],
            auth()->id()
        );

        $this->waCallback = false;

        $this->dispatch('statusSaved', leadId: $this->lead->id);
    }

    private function authorizeAccess(): void
    {
        $user = auth()->user();
        if ($user->isCs()) {
            $handler = $user->handler;
            if (!$handler || $this->lead->handler_id !== $handler->id) {
                abort(403, 'Anda tidak memiliki akses ke lead ini.');
            }
        }
    }

    public function render()
    {
        return view('livewire.update-lead-status');
    }
}
