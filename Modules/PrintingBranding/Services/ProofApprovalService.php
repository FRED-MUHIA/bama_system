<?php

namespace Modules\PrintingBranding\Services;

use Modules\PrintingBranding\Models\Artwork;
use Modules\PrintingBranding\Models\ProofApproval;

class ProofApprovalService
{
    public function sendToClient(Artwork $artwork): ProofApproval
    {
        $approval = ProofApproval::create([
            'artwork_id' => $artwork->id,
            'job_id' => $artwork->job_id,
            'client_id' => $artwork->client_id,
            'status' => 'Sent to Client',
            'sent_at' => now(),
            'audit_trail' => [['event' => 'sent_to_client', 'at' => now()->toISOString(), 'user_id' => auth()->id()]],
        ]);

        $artwork->update(['status' => 'Sent to Client', 'approval_status' => 'Sent to Client']);

        return $approval;
    }

    public function decide(ProofApproval $approval, string $decision, ?string $notes = null): ProofApproval
    {
        $status = $decision === 'approve' ? 'Approved' : 'Revision Requested';
        $trail = $approval->audit_trail ?? [];
        $trail[] = ['event' => $decision, 'at' => now()->toISOString(), 'user_id' => auth()->id(), 'notes' => $notes];

        $approval->update([
            'status' => $status,
            'approval_date' => $decision === 'approve' ? now() : null,
            'approval_notes' => $notes,
            'approved_artwork_version' => $decision === 'approve' ? $approval->artwork?->version : null,
            'audit_trail' => $trail,
        ]);

        $approval->artwork?->update(['status' => $status, 'approval_status' => $status]);
        $approval->job?->update(['status' => $decision === 'approve' ? 'Approved' : 'Artwork In Progress']);

        return $approval->refresh();
    }
}
