<?php

namespace Shared\Compliance\Etims\Contracts;

use App\Models\PosOrder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Shared\Compliance\Etims\Models\EtimsSubmission;

interface EtimsComplianceServiceContract
{
    public function submitSale(PosOrder $order, array $context = []): EtimsSubmission;

    public function submitFiscalDocument(array $document, array $context = []): EtimsSubmission;

    public function queueCreditNote(Model $source, array $document, array $context = []): EtimsSubmission;

    public function queueDebitNote(Model $source, array $document, array $context = []): EtimsSubmission;

    public function retryPending(int $limit = 50): Collection;

    public function metrics(?string $industry = null): array;
}
