<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class Notification extends DatabaseNotification
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'type',
        'notifiable_type',
        'notifiable_id',
        'data',
        'read_at',
        'entity_id',
        'entity_ids',
        'entity_name',
        'notification_title',
        'notification_description',
        'notification_link',
        'sender_id',
        'receiver_id',
        'business_id',
        'is_system_generated',
        'notification_template_id',
        'notification_type',
        'start_date',
        'end_date',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'data' => 'array',
        'entity_ids' => 'array',
        'is_system_generated' => 'boolean',
        'read_at' => 'datetime',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * Get the sender of the notification.
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Get the receiver of the notification.
     */
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    /**
     * Get the business associated with the notification.
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    /**
     * Get the notification template.
     */
    // public function notificationTemplate(): BelongsTo
    // {
    //     return $this->belongsTo(NotificationTemplate::class, 'notification_template_id');
    // }

    /**
     * Scope for system generated notifications.
     */
    public static function scopeSystemGenerated($query)
    {
        return $query->where('is_system_generated', true);
    }

    /**
     * Scope for user generated notifications.
     */
    public static function scopeUserGenerated($query)
    {
        return $query->where('is_system_generated', false);
    }

    /**
     * Scope for active notifications (within date range).
     */
    public static function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('start_date')
                ->orWhere('start_date', '<=', now());
        })->where(function ($q) {
            $q->whereNull('end_date')
                ->orWhere('end_date', '>=', now());
        });
    }

    /**
     * Scope for notifications by type.
     */
    public static function scopeByType($query, $type)
    {
        return $query->where('notification_type', $type);
    }

    /**
     * Scope for notifications by entity.
     */
    public static function scopeByEntity($query, $entityId, $entityName = null)
    {
        $query->where('entity_id', $entityId);

        if ($entityName) {
            $query->where('entity_name', $entityName);
        }

        return $query;
    }

    /**
     * Apply filters from request to the query.
     */
    public static function scopeFilters($query, Request $request)
    {
        // Filter by status (read/unread/all)
        if ($request->has('status') && $request->status !== 'all') {
            if ($request->status === 'read') {
                $query->read();
            } elseif ($request->status === 'unread') {
                $query->unread();
            }
        }

        // Filter by notification type
        if ($request->filled('type')) {
            $query->byType($request->type);
        }

        // Filter by entity
        if ($request->filled('entity_id')) {
            $query->byEntity($request->entity_id, $request->entity_name);
        }

        // Filter by business
        if ($request->filled('business_id')) {
            $query->where('business_id', $request->business_id);
        }

        // Filter by receiver
        if ($request->filled('receiver_id')) {
            $query->where('receiver_id', $request->receiver_id);
        }

        // Filter by system generated
        if ($request->has('is_system_generated')) {
            $query->where('is_system_generated', $request->boolean('is_system_generated'));
        }

        // Filter by date range
        if ($request->filled('start_date')) {
            $query->where('created_at', '>=', $request->start_date . ' 00:00:00');
        }

        if ($request->filled('end_date')) {
            $query->where('created_at', '<=', $request->end_date . ' 23:59:59');
        }

        // Search in title and description
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('notification_title', 'like', "%{$search}%")
                    ->orWhere('notification_description', 'like', "%{$search}%");
            });
        }

        // Order by created_at desc (newest first)
        $query->orderBy('created_at', 'desc');

        return $query;
    }

    /**
     * Mark the notification as read.
     */
    public function markAsRead()
    {
        if (is_null($this->read_at)) {
            $this->update(['read_at' => now()]);
        }

        return $this;
    }

    /**
     * Mark the notification as unread.
     */
    public function markAsUnread()
    {
        $this->update(['read_at' => null]);

        return $this;
    }

    /**
     * Check if the notification is read.
     */
    public function isRead(): bool
    {
        return !is_null($this->read_at);
    }

    /**
     * Check if the notification is unread.
     */
    public function isUnread(): bool
    {
        return is_null($this->read_at);
    }

    /**
     * Check if the notification is active (within date range).
     */
    public function isActive(): bool
    {
        $now = now();

        $startValid = is_null($this->start_date) || $this->start_date <= $now;
        $endValid = is_null($this->end_date) || $this->end_date >= $now;

        return $startValid && $endValid;
    }
}
