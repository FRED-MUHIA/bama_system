<?php

namespace Modules\Agriculture\Models;

use App\Models\Client;
use App\Models\Invoice;

class ProduceSale extends AgricultureModel
{
    protected $table = 'agriculture_produce_sales';
    protected $casts = ['sale_date' => 'date', 'quantity' => 'decimal:3', 'unit_price' => 'decimal:2', 'total' => 'decimal:2'];

    public function farm() { return $this->belongsTo(Farm::class, 'farm_id'); }
    public function client() { return $this->belongsTo(Client::class); }
    public function produceBatch() { return $this->belongsTo(ProduceBatch::class, 'produce_batch_id'); }
    public function invoice() { return $this->belongsTo(Invoice::class); }
}
