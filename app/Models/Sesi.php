<?php

namespace App\Models;

use Database\Factories\SesiFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Table('sesi')]
#[Fillable(['nama', 'jam_mulai', 'jam_selesai'])]
class Sesi extends Model
{
    /** @use HasFactory<SesiFactory> */
    use HasFactory, SoftDeletes;
}
