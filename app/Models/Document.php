<?php

namespace App\Models;

use App\Enums\DocumentType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'vehicle_id',
        'uploaded_by',
        'type',
        'title',
        'file_path',
        'file_hash',
        'mime_type',
        'file_size',
        'expiry_date',
        'ipfs_cid',
        'is_public',
    ];

    protected $casts = [
        'type' => DocumentType::class,
        'expiry_date' => 'date',
        'is_public' => 'boolean',
        'file_size' => 'integer',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
