<?php

declare(strict_types=1);

namespace Tests\fixtures\database\Models;

class City extends TestWPModel
{
    
    public function country()
    {
        return $this->belongsTo(Country::class);
    }
    
    public function activities()
    {
        return $this->belongsToMany(Activity::class);
    }
    
}