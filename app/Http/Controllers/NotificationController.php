<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Notifications",
 *     description="Endpoints to manage notifications"
 * )
 */
class NotificationController extends Controller
{
    /**
     * @OA\Get(
     *     path="/v1.0/notifications",
     *     operationId="getNotifications",
     *     tags={"Notifications"},
     *     summary="Get notifications for authenticated user with filtering and pagination",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Page number for pagination",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         required=false,
     *         description="Number of items per page",
     *         @OA\Schema(type="integer", example=15)
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         required=false,
     *         description="Filter by read status (read/unread/all)",
     *         @OA\Schema(type="string", enum={"read", "unread", "all"}, example="unread")
     *     ),
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         required=false,
     *         description="Filter by notification type",
     *         @OA\Schema(type="string", example="course_enrollment")
     *     ),
     *     @OA\Parameter(
     *         name="entity_id",
     *         in="query",
     *         required=false,
     *         description="Filter by entity ID",
     *         @OA\Schema(type="integer", example=123)
     *     ),
     *     @OA\Parameter(
     *         name="entity_name",
     *         in="query",
     *         required=false,
     *         description="Filter by entity name",
     *         @OA\Schema(type="string", example="course")
     *     ),
     *     @OA\Parameter(
     *         name="business_id",
     *         in="query",
     *         required=false,
     *         description="Filter by receiver business ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="is_system_generated",
     *         in="query",
     *         required=false,
     *         description="Filter by system generated status",
     *         @OA\Schema(type="boolean", example=true)
     *     ),
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         required=false,
     *         description="Filter notifications from this date (Y-m-d)",
     *         @OA\Schema(type="string", format="date", example="2025-01-01")
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         required=false,
     *         description="Filter notifications until this date (Y-m-d)",
     *         @OA\Schema(type="string", format="date", example="2025-12-31")
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         required=false,
     *         description="Search in notification title and description",
     *         @OA\Schema(type="string", example="welcome")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Notifications retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Notifications retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="type", type="string", example="App\\Notifications\\CourseEnrollment"),
     *                     @OA\Property(property="notifiable_type", type="string", example="App\\Models\\User"),
     *                     @OA\Property(property="notifiable_id", type="integer", example=1),
     *                     @OA\Property(property="data", type="object", example={"course_name": "Laravel Basics"}),
     *                     @OA\Property(property="read_at", type="string", format="date-time", nullable=true, example=null),
     *                     @OA\Property(property="entity_id", type="integer", example=123),
     *                     @OA\Property(property="entity_name", type="string", example="course"),
     *                     @OA\Property(property="notification_title", type="string", example="Course Enrollment Successful"),
     *                     @OA\Property(property="notification_description", type="string", example="You have successfully enrolled in Laravel Basics course"),
     *                     @OA\Property(property="notification_link", type="string", nullable=true, example="/dashboard/courses"),
     *                     @OA\Property(property="sender_id", type="integer", example=2),
     *                     @OA\Property(property="business_id", type="integer", nullable=true, example=1),
     *                     @OA\Property(property="is_system_generated", type="boolean", example=false),
     *                     @OA\Property(property="notification_type", type="string", nullable=true, example="course_enrollment"),
     *                     @OA\Property(property="start_date", type="string", format="date", nullable=true, example="2025-01-01"),
     *                     @OA\Property(property="end_date", type="string", format="date", nullable=true, example="2025-12-31"),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time"),
     *                     @OA\Property(
     *                         property="sender",
     *                         type="object",
     *                         nullable=true,
     *                         @OA\Property(property="id", type="integer"),
     *                         @OA\Property(property="name", type="string"),
     *                         @OA\Property(property="email", type="string")
     *                     ),
     *                     @OA\Property(
     *                         property="business",
     *                         type="object",
     *                         nullable=true,
     *                         @OA\Property(property="id", type="integer"),
     *                         @OA\Property(property="name", type="string")
     *                     )
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="per_page", type="integer", example=15),
     *                 @OA\Property(property="total", type="integer", example=50),
     *                 @OA\Property(property="last_page", type="integer", example=4),
     *                 @OA\Property(property="from", type="integer", example=1),
     *                 @OA\Property(property="to", type="integer", example=15)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request - Invalid parameters",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Invalid request parameters.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Authentication required",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="An unexpected error occurred.")
     *         )
     *     )
     * )
     */
    public function getNotifications(Request $request)
    {
        try {
            $user = auth()->user();

            $query = Notification::with(['sender:id,email', 'business:id'])
                ->where('receiver_id', $user->id)
                ->filters($request);

            // Paginate or get all results based on per_page parameter
            if ($request->has('per_page')) {
                $perPage = $request->get('per_page');
                $notifications = $query->paginate($perPage);

                return response()->json([
                    'success' => true,
                    'message' => 'Notifications retrieved successfully',
                    'data' => $notifications->items(),
                    'meta' => [
                        'current_page' => $notifications->currentPage(),
                        'per_page' => $notifications->perPage(),
                        'total' => $notifications->total(),
                        'last_page' => $notifications->lastPage(),
                        'from' => $notifications->firstItem(),
                        'to' => $notifications->lastItem(),
                    ]
                ], 200);
            } else {
                $notifications = $query->get();

                return response()->json([
                    'success' => true,
                    'message' => 'Notifications retrieved successfully',
                    'data' => $notifications,
                    'meta' => [
                        'total' => $notifications->count(),
                    ]
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve notifications',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/v1.0/notifications/business/{business_id}",
     *     operationId="getNotificationsByBusinessId",
     *     tags={"Notifications"},
     *     summary="Get all notifications for a specific business",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="business_id",
     *         in="path",
     *         required=true,
     *         description="Business ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Page number for pagination",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         required=false,
     *         description="Number of items per page",
     *         @OA\Schema(type="integer", example=15)
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         required=false,
     *         description="Filter by read status (read/unread/all)",
     *         @OA\Schema(type="string", enum={"read", "unread", "all"}, example="unread")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Business notifications retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Business notifications retrieved successfully"),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="An unexpected error occurred.")
     *         )
     *     )
     * )
     */
    public function getNotificationsByBusinessId(Request $request, $business_id)
    {
        try {
            $query = Notification::with(['sender:id,email', 'receiver:id,email', 'business:id'])
                ->where('business_id', $business_id)
                ->filters($request);

            // Paginate or get all results based on per_page parameter
            if ($request->has('per_page')) {
                $perPage = $request->get('per_page');
                $notifications = $query->paginate($perPage);

                return response()->json([
                    'success' => true,
                    'message' => 'Business notifications retrieved successfully',
                    'data' => $notifications->items(),
                    'meta' => [
                        'current_page' => $notifications->currentPage(),
                        'per_page' => $notifications->perPage(),
                        'total' => $notifications->total(),
                        'last_page' => $notifications->lastPage(),
                        'from' => $notifications->firstItem(),
                        'to' => $notifications->lastItem(),
                    ]
                ], 200);
            } else {
                $notifications = $query->get();

                return response()->json([
                    'success' => true,
                    'message' => 'Business notifications retrieved successfully',
                    'data' => $notifications,
                    'meta' => [
                        'total' => $notifications->count(),
                    ]
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve business notifications',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Patch(
     *     path="/v1.0/notifications/{id}/status",
     *     operationId="updateNotificationStatus",
     *     tags={"Notifications"},
     *     summary="Update notification read status",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Notification ID (UUID)",
     *         @OA\Schema(type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"is_read"},
     *             @OA\Property(property="is_read", type="boolean", example=true, description="Mark as read (true) or unread (false)")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Notification status updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Notification status updated successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Notification not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Notification not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="An unexpected error occurred.")
     *         )
     *     )
     * )
     */
    public function updateNotificationStatus(Request $request, $id)
    {
        try {
            $request->validate([
                'is_read' => 'required|boolean'
            ]);

            $user = auth()->user();
            $notification = Notification::where('id', $id)
                ->where('receiver_id', $user->id)
                ->first();

            if (!$notification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notification not found'
                ], 404);
            }

            if ($request->is_read) {
                $notification->markAsRead();
            } else {
                $notification->markAsUnread();
            }

            return response()->json([
                'success' => true,
                'message' => 'Notification status updated successfully',
                'data' => $notification->fresh()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update notification status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/v1.0/notifications/mark-all-read",
     *     operationId="markAllNotificationsAsRead",
     *     tags={"Notifications"},
     *     summary="Mark all notifications as read for authenticated user",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="All notifications marked as read successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="All notifications marked as read"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="updated_count", type="integer", example=15)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="An unexpected error occurred.")
     *         )
     *     )
     * )
     */
    public function markAllNotificationsAsRead(Request $request)
    {
        try {
            $user = auth()->user();

            $updatedCount = Notification::where('receiver_id', $user->id)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);

            return response()->json([
                'success' => true,
                'message' => 'All notifications marked as read',
                'data' => [
                    'updated_count' => $updatedCount
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark notifications as read',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/v1.0/notifications/{id}",
     *     operationId="deleteNotificationById",
     *     tags={"Notifications"},
     *     summary="Delete a specific notification",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Notification ID (UUID)",
     *         @OA\Schema(type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Notification deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Notification deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Notification not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Notification not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="An unexpected error occurred.")
     *         )
     *     )
     * )
     */
    public function deleteNotificationById($id)
    {
        try {
            $user = auth()->user();
            $notification = Notification::where('id', $id)
                ->where('receiver_id', $user->id)
                ->first();

            if (!$notification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notification not found'
                ], 404);
            }

            $notification->delete();

            return response()->json([
                'success' => true,
                'message' => 'Notification deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete notification',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
