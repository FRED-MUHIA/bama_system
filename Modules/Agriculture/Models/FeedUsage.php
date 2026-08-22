<?php

namespace Modules\Agriculture\Models;

class FeedUsage extends AgricultureModel
{
    protected $table = 'agriculture_feed_usages';
    protected $casts = ['usage_date' => 'date', 'quantity' => 'decimal:3', 'cost' => 'decimal:2'];

    public function farm() { return $this->belongsTo(Farm::class, 'farm_id'); }
    public function feedType() { return $this->belongsTo(FeedType::class, 'feed_type_id'); }
    public function animal() { return $this->belongsTo(Animal::class, 'animal_id'); }
    public function herd() { return $this->belongsTo(Herd::class, 'herd_id'); }
}
