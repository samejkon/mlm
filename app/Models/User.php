<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'parent_id'];

    public function parent()
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(User::class, 'parent_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function commissions()
    {
        return $this->hasMany(Commission::class);
    }

    public function descendants(int $maxDepth = PHP_INT_MAX, int $currentDepth = 1): Collection
    {
        $descendants = new Collection();

        if ($currentDepth > $maxDepth) {
            return $descendants;
        }

        foreach ($this->children as $child) {
            $descendants->push($child);
            $descendants = $descendants
                ->merge($child->descendants($maxDepth, $currentDepth + 1));
        }

        return $descendants;
    }
}
