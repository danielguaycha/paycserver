<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    public $timestamps = false;

    public function person() {
        return $this->belongsTo('App\Person');
    }

    public function data() {
        $this->person;
        return $this;
    }
}
