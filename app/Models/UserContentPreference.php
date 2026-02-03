<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserContentPreference extends Model
{
    protected $fillable = [
        'user_id',
        'industries',
        'topics',
        'keywords',
        'min_engagement',
        'date_range',
        'preferred_post_types',
        'favorite_creators',
        'fetch_from_creators',
        'fetch_from_keywords',
        'smart_fetch',
        'fetch_meta',
    ];

    protected $casts = [
        'industries' => 'array',
        'topics' => 'array',
        'keywords' => 'array',
        'preferred_post_types' => 'array',
        'favorite_creators' => 'array',
        'fetch_from_creators' => 'boolean',
        'fetch_from_keywords' => 'boolean',
        'min_engagement' => 'integer',
        'smart_fetch' => 'boolean',
    ];

    /**
     * Get the user that owns the preferences
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Get all search keywords (industries + topics + custom keywords)
     */
    public function getAllKeywords(): array
    {
        $keywords = [];
        
        if ($this->industries) {
            $keywords = array_merge($keywords, $this->industries);
        }
        
        if ($this->topics) {
            $keywords = array_merge($keywords, $this->topics);
        }
        
        if ($this->keywords) {
            $keywords = array_merge($keywords, $this->keywords);
        }
        
        return array_unique($keywords);
    }
    
    /**
     * Get default preferences for new users
     */
    public static function getDefaults(): array
    {
        return [
            'industries' => ['Business & Entrepreneurship', 'Technology & Software', 'Marketing & Advertising'],
            'topics' => ['entrepreneurship', 'leadership', 'innovation'],
            'min_engagement' => 100, // 100+ likes for quality viral content
            'date_range' => 'past-month', // Posts from last month have time to accumulate engagement
            'fetch_from_keywords' => true,
            'fetch_from_creators' => false,
            'smart_fetch' => false,
        ];
    }
}
