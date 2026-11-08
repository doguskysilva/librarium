<?php

namespace App\Models;

use Database\Factories\AuthorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'biography'])]
class Author extends Model
{
    /** @use HasFactory<AuthorFactory> */
    use HasFactory, HasUuids;

    protected $keyType = 'string';

    public $incrementing = false;
}
