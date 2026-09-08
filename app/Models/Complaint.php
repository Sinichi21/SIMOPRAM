<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Complaint extends Model
{
    use HasFactory;

    public const CATEGORIES = ['Layanan aplikasi', 'Kegiatan Pramuka', 'Presensi dan penilaian', 'Sarana dan fasilitas', 'Perilaku dan keamanan', 'Lainnya'];

    public const STATUSES = ['new' => 'Baru', 'processing' => 'Diproses', 'resolved' => 'Selesai', 'rejected' => 'Ditolak'];

    protected $fillable = [
        'reference', 'school_id', 'user_id', 'name', 'email', 'category',
        'subject', 'body', 'attachment_path', 'status', 'response', 'responded_by',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
