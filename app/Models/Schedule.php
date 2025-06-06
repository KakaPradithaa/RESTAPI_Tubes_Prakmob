<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'day',
        'open_time',
        'close_time',
        'max_booking_per_slot'
    ];

    /**
     * Get all of the bookings for the schedule.
     */
    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }
}
