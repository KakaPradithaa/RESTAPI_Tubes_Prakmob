<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'vehicle_id',
        'service_id',
        'schedule_id', // Ini penting untuk relasi
        'booking_date',
        'booking_time',
        'status',
        'complaint'
    ];

    /**
     * Get the user that owns the booking.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the vehicle associated with the booking.
     */
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Get the service associated with the booking.
     */
    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Get the schedule associated with the booking.
     */
    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }
}
