<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Models\Student;
use App\Models\Teacher;
use App\Models\Attendance;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Facades\Storage;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'profile_photo_path',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function teacher()
    {
        return $this->hasOne(Teacher::class);
    }

    public function student()
    {
        return $this->hasOne(Student::class);
    }

    public function generateQrToken()
    {
        // Generate token unik jika belum ada
        if (!$this->qr_token) {
            $this->qr_token = Str::uuid();
            $this->save();
        }
        return $this->qr_token;
    }

    // Method untuk regenerate token jika diperlukan
    public function refreshQrToken()
    {
        $this->qr_token = Str::uuid();
        $this->save();
        return $this->qr_token;
    }

    public function getQrCodeUrlAttribute()
    {
        return $this->qr_code_path
            ? Storage::url($this->qr_code_path)
            : null;
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    // Dalam model User
    public function getQrScanUrl()
    {
        return route('attendance.scan', ['token' => $this->qr_token]);
    }

    public function getProfilePhotoUrlAttribute()
    {
        if ($this->profile_photo_path) {
            return Storage::url($this->profile_photo_path);
        }

        // Fallback ke Gravatar atau avatar default
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&color=7F9CF5&background=EBF4FF';
    }
}
