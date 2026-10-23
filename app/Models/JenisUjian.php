<?php

namespace App\Models;

use Database\Factories\JenisUjianFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Table('jenis_ujian')]
#[Fillable(['nama', 'kode'])]
class JenisUjian extends Model
{
    /** @use HasFactory<JenisUjianFactory> */
    use HasFactory, SoftDeletes;

    protected function kode(): Attribute
    {
        return Attribute::make(
            set: fn (string $value): string => Str::upper(trim($value)),
        );
    }
}
