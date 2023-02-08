<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Result extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * Indicates if the model's ID is not auto-incrementing hence don't cast to integer.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The primary key is an UUID so we are setting key type to string.
     *
     * @var string
     */
    protected $keyType = 'string';

    public function homeTeam() {
        return $this->belongsTo(Team::class, 'home_team_id');
    }

    public function awayTeam() {
        return $this->belongsTo(Team::class, 'away_team_id');
    }
}
