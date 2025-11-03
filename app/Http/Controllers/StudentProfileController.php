<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateStudentProfileRequest;
use App\Models\SocialLink;
use App\Models\StudentProfile;
use Illuminate\Http\Request;

class StudentProfileController extends Controller
{
    /**
     * @OA\Patch(
     *   path="/v1.0/student-profile",
     *   tags={"student_management.profile"},
     *   summary="Update student profile",
     *   description="Updates the authenticated student's profile. Partial updates supported.",
     *   operationId="updateStudentProfile",
     *   security={{"bearerAuth":{}}},
     *
     *   @OA\RequestBody(
     *     required=false,
     *     @OA\JsonContent(
     *       @OA\Property(property="bio", type="string", example="Passionate learner in tech and science", maxLength=500),
     *       @OA\Property(property="address_line_1", type="string", example="123 Main St, City", maxLength=255),
     *       @OA\Property(
     *         property="learning_preferences",
     *         type="object",
     *         @OA\Property(property="preferred_learning_time", type="string", enum={"morning","afternoon","evening","night"}, example="morning"),
     *         @OA\Property(property="daily_goal", type="integer", example=8, minimum=1, maximum=24)
     *       ),
     *       @OA\Property(
     *         property="interests",
     *         type="array",
     *         @OA\Items(type="string", maxLength=100),
     *         example={"AI","Reading"}
     *       ),
     *       @OA\Property(
     *         property="social_links",
     *         type="object",
     *         @OA\Property(property="web_site", type="string", format="uri", example="https://myportfolio.example"),
     *         @OA\Property(property="facebook", type="string", format="uri", example="https://facebook.com/username"),
     *         @OA\Property(property="linkedin", type="string", format="uri", example="https://linkedin.com/in/username"),
     *         @OA\Property(property="github", type="string", format="uri", example="https://github.com/username"),
     *         @OA\Property(property="twitter", type="string", format="uri", example="https://twitter.com/username")
     *       )
     *     )
     *   ),
     *
     *   @OA\Response(
     *     response=200,
     *     description="Profile updated successfully",
     *     @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="message", type="string", example="Profile updated successfully"),
     *       @OA\Property(
     *         property="data",
     *         type="object",
     *         @OA\Property(
     *           property="profile",
     *           type="object",
     *           @OA\Property(property="user_id", type="integer", example=1),
     *           @OA\Property(property="bio", type="string", example="Passionate learner in tech and science"),
     *           @OA\Property(property="address_line_1", type="string", example="123 Main St, City"),
     *           @OA\Property(
     *             property="learning_preferences",
     *             type="object",
     *             @OA\Property(property="preferred_learning_time", type="string", example="morning"),
     *             @OA\Property(property="daily_goal", type="integer", example=8)
     *           ),
     *           @OA\Property(
     *             property="interests",
     *             type="array",
     *             @OA\Items(type="string"),
     *             example={"AI","Reading"}
     *           )
     *         ),
     *         @OA\Property(
     *           property="social_links",
     *           type="object",
     *           @OA\Property(property="web_site", type="string", format="uri"),
     *           @OA\Property(property="facebook", type="string", format="uri"),
     *           @OA\Property(property="linkedin", type="string", format="uri"),
     *           @OA\Property(property="github", type="string", format="uri"),
     *           @OA\Property(property="twitter", type="string", format="uri")
     *         )
     *       )
     *     )
     *   ),
     *
     *   @OA\Response(response=403, description="Forbidden"),
     *   @OA\Response(response=422, description="Validation error")
     * )
     */


    public function updateStudentProfile(UpdateStudentProfileRequest $request)
    {
        $data = $request->validated();
        $user = auth()->user();

        // Profile
        $profile = StudentProfile::firstOrNew(['user_id' => $user->id]);
        // Don’t let random keys slip into fill()
        $profilePayload = collect($data)->only([
            'bio',
            'address_line_1',
            'learning_preferences',
            'interests'
        ])->toArray();

        $profile->fill($profilePayload);
        $profile->user_id = $user->id; // for first create
        $profile->save();

        // Social links (one row per user)
        if (!empty($data['social_links']) && is_array($data['social_links'])) {
            $socialPayload = collect($data['social_links'])->only([
                'web_site',
                'facebook',
                'linkedin',
                'github',
                'twitter'
            ])->map(function ($v) {
                // Normalize empty strings to null so DB remains clean
                return is_string($v) && trim($v) === '' ? null : $v;
            })->toArray();

            $social = SocialLink::updateOrCreate(
                ['user_id' => $user->id],
                $socialPayload + ['user_id' => $user->id]
            );
        } else {
            $social = $user->socialLink; // might be null
        }

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data'    => [
                'profile'      => $profile,
                'social_links' => $social,
            ],
        ], 200);
    }
}
