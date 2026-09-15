<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoryAssignment extends Model
{
    protected $fillable = ['category', 'office'];

    public static function categoriesForOffice(string $office): array
    {
        return self::query()
            ->where('office', $office)
            ->pluck('category')
            ->all();
    }
}
