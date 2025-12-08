<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateStudentProfileRequest;
use App\Models\SocialLink;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class StudentProfileController extends Controller
{
    private function putSingleFile(?UploadedFile $file, string $folderPath, ?string $oldFilename = null): ?string
    {
        if (!$file) {
            return $oldFilename;
        }

        $new = $file->hashName();
        $file->storeAs($folderPath, $new, 'public');

        if ($oldFilename) {
            $old = "{$folderPath}/{$oldFilename}";
            if (Storage::disk('public')->exists($old)) {
                Storage::disk('public')->delete($old);
            }
        }

        return $new;
    }


    /**
     * @OA\Patch(
     *   path="/v1.0/student-profile/{id}",
     *   tags={"student_management.profile"},
     *   summary="Update student profile (and basic user fields)",
     *   description="Partial updates supported. multipart/form-data allows profile_photo upload.",
     *   operationId="updateStudentProfile",
     *   security={{"bearerAuth":{}}},
     *
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     description="User ID to update",
     *     @OA\Schema(type="integer"),
     *     example=1
     *   ),
     *
     *   @OA\RequestBody(
     *     required=false,
     *     @OA\MediaType(
     *       mediaType="multipart/form-data",
     *       @OA\Schema(
     *         type="object",
     *         @OA\Property(property="bio", type="string", maxLength=500, example="Passionate learner in tech and science"),
     *         @OA\Property(property="address_line_1", type="string", maxLength=255, example="123 Main St, City"),
     *
     *         @OA\Property(property="learning_preferences[preferred_learning_time]", type="string", enum={"morning","afternoon","evening","night"}, example="morning"),
     *         @OA\Property(property="learning_preferences[daily_goal]", type="integer", minimum=1, maximum=24, example=8),
     *
     *         @OA\Property(
     *           property="interests[]",
     *           type="array",
     *           @OA\Items(type="string", maxLength=100),
     *           description="Send multiple interests[] parts",
     *           example={"AI","Reading"}
     *         ),
     *
     *         @OA\Property(property="social_links[web_site]", type="string", format="uri", example="https://myportfolio.example"),
     *         @OA\Property(property="social_links[facebook]", type="string", format="uri", example="https://facebook.com/username"),
     *         @OA\Property(property="social_links[linkedin]", type="string", format="uri", example="https://linkedin.com/in/username"),
     *         @OA\Property(property="social_links[github]", type="string", format="uri", example="https://github.com/username"),
     *         @OA\Property(property="social_links[twitter]", type="string", format="uri", example="https://twitter.com/username"),
     *
     *         @OA\Property(property="user[title]", type="string", example="Mr."),
     *         @OA\Property(property="user[first_name]", type="string", example="John"),
     *         @OA\Property(property="user[last_name]", type="string", example="Doe"),
     *         @OA\Property(property="user[phone]", type="string", example="+8801765432109"),
     *         @OA\Property(
     *           property="user[profile_photo]",
     *           type="string",
     *           format="binary",
     *           description="Image file (jpg, jpeg, png, webp, avif, gif). Max 5MB."
     *         )
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
     *           property="user",
     *           type="object",
     *           @OA\Property(property="id", type="integer", example=1),
     *           @OA\Property(property="title", type="string", example="Mr."),
     *           @OA\Property(property="first_name", type="string", example="John"),
     *           @OA\Property(property="last_name", type="string", example="Doe"),
     *           @OA\Property(property="phone", type="string", example="+8801765432109"),
     *           @OA\Property(property="profile_photo", type="string", example="http://yourapp.com/storage/users/1/abc123.jpg")
     *         ),
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
     *           @OA\Property(property="interests", type="array", @OA\Items(type="string"), example={"AI","Reading"})
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
     *   @OA\Response(response=422, description="Validation error")
     * )
     */




    public function updateStudentProfile(UpdateStudentProfileRequest $request, int $id)
    {
        $payload_data = $request->validated();

        DB::beginTransaction();
        try {
            // GET USER
            $user = User::with([
                'student_profile',
                'social_links'
            ])->findOrFail($id);

            // === Update User fields ===
            $userPayload['title'] = $payload_data['user']['title'] ?? null;
            $userPayload['first_name'] = $payload_data['user']['first_name'] ?? null;
            $userPayload['last_name'] = $payload_data['user']['last_name'] ?? null;
            $userPayload['phone'] = $payload_data['user']['phone'] ?? null;

            // Handle profile_photo file upload (optional)
            $folder_path = "business_1/profile_photo_{$user->id}";
            if ($request->hasFile('profile_photo')) {
                $photo_filename = $this->putSingleFile(
                    $request->file('profile_photo'),
                    $folder_path,
                    $user->getRawOriginal('profile_photo')
                );
                $userPayload['profile_photo'] = $photo_filename;
            } elseif ($request->filled('profile_photo') && is_string($request->input('profile_photo'))) {
                // If profile_photo is sent as a string (existing filename)
                $userPayload['profile_photo'] = basename($request->input('profile_photo'));
            }
            // If neither condition is met, don't modify profile_photo (keep existing)

            // Update user only if there's data to update
            if (!empty($userPayload)) {
                $user->update($userPayload);
            }

            // === Update Student Profile ===
            $profile = StudentProfile::firstOrNew(['user_id' => $user->id]);

            $profilePayload['bio'] = $payload_data['bio'] ?? null;
            $profilePayload['address_line_1'] = $payload_data['address_line_1'] ?? null;
            $profilePayload['learning_preferences'] = $payload_data['learning_preferences'] ?? null;
            $profilePayload['interests'] = $payload_data['interests'] ?? null;

            if (!empty($profilePayload)) {
                $profile->fill($profilePayload);
                $profile->user_id = $user->id;
                $profile->save();
            }

            // === Upsert Social Links ===
            $socialPayload['web_site'] = $payload_data['social_links']['web_site'] ?? null;
            $socialPayload['facebook'] = $payload_data['social_links']['facebook'] ?? null;
            $socialPayload['linkedin'] = $payload_data['social_links']['linkedin'] ?? null;
            $socialPayload['github'] = $payload_data['social_links']['github'] ?? null;
            $socialPayload['twitter'] = $payload_data['social_links']['twitter'] ?? null;

            if (!empty($payload_data['social_links']) && is_array($payload_data['social_links'])) {
                SocialLink::updateOrCreate(
                    ['user_id' => $user->id],
                    array_merge($socialPayload, ['user_id' => $user->id])
                );
            }

            // Refresh user with all relationships
            $user->load([
                'student_profile',
                'social_links'
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'User Profile updated successfully',
                'data'    => $user,
            ], 200);
        } catch (\Throwable $e) {
            DB::rollBack();

            // Log the error for debugging
            // \Log::error('Student profile update failed', [
            //     'user_id' => $id,
            //     'error' => $e->getMessage(),
            //     'trace' => $e->getTraceAsString()
            // ]);

            throw $e;
        }
    }
}
