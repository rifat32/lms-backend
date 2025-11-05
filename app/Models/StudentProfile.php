<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentProfile extends Model
{
    protected $table = 'student_profiles';
    protected $primaryKey = 'user_id'; // As discussed earlier
    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'bio',
        'address_line_1',
        'learning_preferences',
        'interests',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'learning_preferences' => 'object', // Auto json_decode to stdClass on get, json_encode on set
        'interests' => 'array',            // Optional: Do the same for interests if it makes sense (e.g., if it's a key-value structure)
    ];

    // Relationship to User
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function socialLink()
    {
        return $this->belongsTo(SocialLink::class);
    }
}
