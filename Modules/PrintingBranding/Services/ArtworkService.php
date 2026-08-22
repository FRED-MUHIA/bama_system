<?php

namespace Modules\PrintingBranding\Services;

use Modules\PrintingBranding\Models\Artwork;
use Modules\PrintingBranding\Models\ProductionJob;

class ArtworkService
{
    public function __construct(private PrintingNumberService $numbers) {}

    public function uploadVersion(ProductionJob $job, array $data): Artwork
    {
        $latest = Artwork::where('job_id', $job->id)->orderByDesc('version')->first();

        return Artwork::create($data + [
            'client_id' => $job->client_id,
            'job_id' => $job->id,
            'artwork_number' => $latest?->artwork_number ?: $this->numbers->next('ART', Artwork::class, 'artwork_number'),
            'version' => $latest ? $latest->version + 1 : 1,
            'uploaded_at' => now(),
            'approval_status' => $data['approval_status'] ?? 'Received',
            'status' => $data['status'] ?? 'Received',
        ]);
    }
}
