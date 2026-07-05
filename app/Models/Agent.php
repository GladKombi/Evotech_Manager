<?php
namespace App\Models;
use App\Core\Model;
class Agent extends Model {
    protected string $table = 'AGENTS';
    protected string $primaryKey = 'agent_id';
}
