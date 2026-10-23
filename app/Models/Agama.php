<?php

namespace App\Models;

use Database\Factories\AgamaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Table('agama')]
#[Fillable(['nama'])]
class Agama extends Model
{
    /** @use HasFactory<AgamaFactory> */
    use HasFactory, SoftDeletes;
}
