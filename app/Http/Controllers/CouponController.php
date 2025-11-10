<?php

namespace App\Http\Controllers;

use App\Http\Requests\CouponCreateRequest;
use App\Http\Requests\CouponToggleActiveRequest;
use App\Http\Requests\CouponUpdateRequest;
use App\Models\Coupon;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    /**
     * @OA\Post(
     *      path="/v1.0/coupons",
     *      operationId="createCoupon",
     *      tags={"coupon_management"},
     *      summary="Create a new coupon",
     *      description="Create a new LMS coupon",
     *      security={{"bearerAuth": {}}},
     *      @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"name","code","discount_type","discount_amount","coupon_start_date","coupon_end_date"},

     *            @OA\Property(property="name", type="string", example="New Year Offer"),
     *            @OA\Property(property="code", type="string", example="NY2025"),
     *            @OA\Property(property="discount_type", type="string", example="percentage"),
     *            @OA\Property(property="discount_amount", type="number", example=15),
     *            @OA\Property(property="coupon_start_date", type="string", example="2025-01-01"),
     *            @OA\Property(property="coupon_end_date", type="string", example="2025-12-31"),
     *         )
     *      ),
     *      @OA\Response(response=201, description="Created"),
     *      @OA\Response(response=400, description="Bad Request"),
     *      @OA\Response(response=401, description="Unauthorized"),
     *      @OA\Response(response=422, description="Validation Error")
     * )
     */
    public function createCoupon(CouponCreateRequest $request)
    {
        $coupon = Coupon::create($request->validated());
        return response()->json(
            [
                'success' => true,
                'message' => 'Coupon created successfully',
                'data' => $coupon
            ],
            201
        );
    }

    /**
     * @OA\Patch(
     *      path="/v1.0/coupons/{id}",
     *      operationId="updateCoupon",
     *      tags={"coupon_management"},
     *      summary="Update an existing coupon",
     *      description="Update an existing LMS coupon",
     *      security={{"bearerAuth": {}}},
     *      @OA\Parameter(
     *          name="id",
     *          description="Coupon ID",
     *          required=true,
     *          in="path",
     *          @OA\Schema(type="integer")
     *      ),
     *      @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            @OA\Property(property="id", type="string", example="1"),
     *            @OA\Property(property="name", type="string", example="Summer Sale"),
     *            @OA\Property(property="code", type="string", example="SUMMER25"),
     *            @OA\Property(property="discount_type", type="string", example="percentage"),
     *            @OA\Property(property="discount_amount", type="number", example=25),
     *            @OA\Property(property="coupon_start_date", type="string", example="2025-05-01"),
     *            @OA\Property(property="coupon_end_date", type="string", example="2025-08-31"),
     *         )
     *      ),
     *      @OA\Response(response=200, description="Updated successfully"),
     *      @OA\Response(response=404, description="Coupon not found"),
     *      @OA\Response(response=422, description="Validation Error")
     * )
     */
    public function updateCoupon(CouponUpdateRequest $request, $id)
    {
        if (!auth()->user()->hasAnyRole(['owner', 'admin', 'lecturer'])) {
            return response()->json([
                "message" => "You can not perform this action"
            ], 401);
        }

        $request_payload = $request->validated();

        $coupon = Coupon::find($id);

        if (!$coupon) {
            return response()->json([
                'success' => false,
                'message' => 'Coupon not found'
            ], 404);
        }

        $coupon->update($request_payload);

        return response()->json([
            'success' => true,
            'message' => 'Coupon updated successfully',
            'coupon' => $coupon
        ], 200);
    }

    /**
     * @OA\Get(
     *      path="/v1.0/coupons",
     *      operationId="getAllCoupons",
     *      tags={"coupon_management"},
     *      summary="Get all coupons with filters and pagination",
     *      description="Fetch all coupons for the LMS system, filtered and paginated",
     *      security={{"bearerAuth": {}}},
     *      @OA\Parameter(
     *          name="search",
     *          in="query",
     *          description="Search by coupon name or code",
     *          required=false,
     *          @OA\Schema(type="string")
     *      ),
     *      @OA\Parameter(
     *          name="is_active",
     *          in="query",
     *          description="Filter by active status (1 or 0)",
     *          required=false,
     *          @OA\Schema(type="boolean")
     *      ),
     *      @OA\Parameter(
     *          name="start_date",
     *          in="query",
     *          description="Filter coupons starting after this date",
     *          required=false,
     *          @OA\Schema(type="string", format="date")
     *      ),
     *      @OA\Parameter(
     *          name="end_date",
     *          in="query",
     *          description="Filter coupons ending before this date",
     *          required=false,
     *          @OA\Schema(type="string", format="date")
     *      ),
     *      @OA\Parameter(
     *          name="per_page",
     *          in="query",
     *          description="Number of results per page (default 10)",
     *          required=false,
     *          @OA\Schema(type="integer")
     *      ),
     *      @OA\Response(response=200, description="Successful Operation"),
     *      @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function getAllCoupons(Request $request)
    {
        // AuthZ
        if (!auth()->user()->hasAnyRole(['owner', 'admin', 'lecturer'])) {
            return response()->json([
                'message' => 'You can not perform this action'
            ], 403); // use 403 for forbidden
        }


        // Base query with model scope
        $query = Coupon::query()->filters();

        // Data list (keep your helper)
        $coupons = retrieve_data($query, 'created_at', 'coupons');

        // For summary, reuse the same filters
        $forSummary = clone $query;

        $summary = [
            // total under the same filters (ignores pagination)
            'total_coupons'        => (clone $forSummary)->count(),

            // active under the same filters + active scope (date-aware)
            'active_coupons'       => (clone $forSummary)->active()->count(),

            // sum only FIXED coupons' discount_amount under same filters
            'fixed_discount_amount'   => (clone $forSummary)
                ->where('discount_type', 'fixed')
                ->sum('discount_amount'),
        ];

        // Fallback shape
        return response()->json([
            'success' => true,
            'message' => 'All coupons retrieved successfully',
            'summary' => $summary,
            'meta'    => $coupons['meta'],
            'data'    => $coupons['data'],
        ], 200);
    }




    /**
     * @OA\Patch(
     *      path="/v1.0/coupons/{id}/toggle-active",
     *      operationId="toggleActiveCoupon",
     *      tags={"coupon_management"},
     *      summary="Toggle coupon active status",
     *      description="Toggle the is_active flag of a coupon (requires coupon_update permission)",
     *      security={{"bearerAuth": {}}},
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          description="Coupon ID",
     *          required=true,
     *          @OA\Schema(type="integer", example=1)
     *      ),
     *      @OA\Response(response=200, description="Coupon status updated successfully"),
     *      @OA\Response(response=401, description="Unauthorized"),
     *      @OA\Response(response=404, description="Coupon not found"),
     *      @OA\Response(response=422, description="Validation Error")
     * )
     */
    public function toggleActiveCoupon(Request $request, $id)
    {
        try {
            if (!auth()->user()->hasAnyRole(['owner', 'admin', 'lecturer'])) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            // Find the coupon by route parameter
            $coupon = Coupon::findOrFail($id);

            $coupon->is_active = !$coupon->is_active;
            $coupon->save();

            return response()->json([
                'success' => true,
                'message' => 'Coupon status updated successfully',
                'data' => $coupon
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Coupon not found with id: ' . $id
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *      path="/v1.0/coupons/{id}",
     *      operationId="deleteCouponById",
     *      tags={"coupon_management"},
     *      summary="Delete coupon by id",
     *      description="Deletes a coupon for a specific garage (requires coupon_delete permission)",
     *      security={{"bearerAuth": {}}},
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          description="Coupon ID",
     *          required=true,
     *          example=6
     *      ),
     *      @OA\Response(response=200, description="Coupon deleted successfully"),
     *      @OA\Response(response=401, description="Unauthorized"),
     *      @OA\Response(response=404, description="Coupon not found")
     * )
     */
    public function deleteCouponById($id, Request $request)
    {
        try {

            if (!auth()->user()->hasAnyRole(['owner', 'admin', 'lecturer'])) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }


            $coupon = Coupon::where('id', $id)
                ->first();

            if (!$coupon) {
                return response()->json([
                    'success' => false,
                    'message' => 'Coupon not found'
                ], 404);
            }

            $coupon->delete();

            return response()->json([
                'success' => true,
                'message' => 'Coupon deleted successfully',
            ], 200);
        } catch (\Exception $e) {

            return response()->json(['message' => $e->getMessage()], 500);
        }
    }


    /**
     * Apply a coupon to calculate the final amount.
     *
     * @OA\Post(
     *     path="/v1.0/coupons/apply",
     *     operationId="applyCoupon",
     *     tags={"coupon_management"},
     *     summary="Apply coupon code to total amount",
     *     description="Applies a valid coupon to the provided total amount and returns the discount and final amount.",
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         description="Coupon application details",
     *         required=true,
     *         @OA\JsonContent(
     *             required={"coupon_code", "total_amount"},
     *             @OA\Property(property="coupon_code", type="string", example="DISCOUNT10", description="The coupon code to apply"),
     *             @OA\Property(property="total_amount", type="number", format="float", example=100.00, description="The total amount before discount")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Coupon applied successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Coupon applied successfully"),
     *             @OA\Property(property="discount_amount", type="number", format="float", example=10.00),
     *             @OA\Property(property="final_amount", type="number", format="float", example=90.00)
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Invalid or expired coupon code",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Invalid or expired coupon code")
     *         )
     *     )
     * )
     */


    public function applyCoupon(Request $request)
    {
        // Validate the incoming request
        $request->validate([
            'coupon_code' => 'required|string',
            'total_amount' => 'required|numeric|min:0',
        ]);

        $total_amount = $request->total_amount;
        $discount_amount = 0;
        $coupon_code = $request->coupon_code;

        if (!empty($coupon_code)) {
            $coupon = Coupon::where('code', $coupon_code)
                ->where('is_active', true)
                ->where(function ($query) {
                    $query->whereNull('coupon_start_date')
                        ->orWhereDate('coupon_start_date', '<=', now());
                })
                ->where(function ($query) {
                    $query->whereNull('coupon_end_date')
                        ->orWhereDate('coupon_end_date', '>=', now());
                })
                ->first();

            if (!$coupon) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired coupon code'
                ], 422);
            }

            // Calculate discount based on type (flat or percent)
            if ($coupon->discount_type === Coupon::DISCOUNT_TYPE['PERCENTAGE']) {
                $discount_amount = ($total_amount * $coupon->discount_amount) / 100;
            } else {
                $discount_amount = $coupon->discount_amount;
            }

            // Prevent over-discount
            $discount_amount = min($discount_amount, $total_amount);
            $total_amount -= $discount_amount;
        }

        // Return the final amount in the response
        return response()->json([
            'success' => true,
            'message' => 'Coupon applied successfully',
            'data' => [
                'discount_amount' => $discount_amount,
                'final_amount' => $total_amount
            ]
        ], 200);
    }
}
