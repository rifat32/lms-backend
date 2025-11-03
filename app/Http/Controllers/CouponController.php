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
     *            @OA\Property(property="min_total", type="number", example=50),
     *            @OA\Property(property="max_total", type="number", example=500),
     *            @OA\Property(property="redemptions", type="integer", example=10),
     *            @OA\Property(property="coupon_start_date", type="string", example="2025-01-01"),
     *            @OA\Property(property="coupon_end_date", type="string", example="2025-12-31"),
     *            @OA\Property(property="is_auto_apply", type="boolean", example=true),
     *            @OA\Property(property="is_active", type="boolean", example=true),
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
        return response()->json($coupon, 201);
    }

    /**
     * @OA\Put(
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

     *            @OA\Property(property="name", type="string", example="Summer Sale"),
     *            @OA\Property(property="code", type="string", example="SUMMER25"),
     *            @OA\Property(property="discount_type", type="string", example="percentage"),
     *            @OA\Property(property="discount_amount", type="number", example=25),
     *            @OA\Property(property="min_total", type="number", example=100),
     *            @OA\Property(property="max_total", type="number", example=500),
     *            @OA\Property(property="redemptions", type="integer", example=50),
     *            @OA\Property(property="coupon_start_date", type="string", example="2025-05-01"),
     *            @OA\Property(property="coupon_end_date", type="string", example="2025-08-31"),
     *            @OA\Property(property="is_auto_apply", type="boolean", example=false),
     *            @OA\Property(property="is_active", type="boolean", example=true),
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

        $coupon = Coupon::find($id);

        if (!$coupon) {
            return response()->json(['message' => 'Coupon not found'], 404);
        }

        $coupon->update($request->validated());

        return response()->json([
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
    public function getAllCoupons()
    {
          if (!auth()->user()->hasAnyRole(['owner', 'admin', 'lecturer'])) {
            return response()->json([
                "message" => "You can not perform this action"
            ], 401);
        }

    
        $search = request()->input('search');
        $is_active = request()->input('is_active');
        $start_date = request()->input('start_date');
        $end_date = request()->input('end_date');

        $query = Coupon::query()
            ->when($search, function ($q) use ($search) {
                $q->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->when(!is_null($is_active), function ($q) use ($is_active) {
                $q->where('is_active', (bool) $is_active);
            })
            ->when($start_date, function ($q) use ($start_date) {
                $q->whereDate('coupon_start_date', '>=', $start_date);
            })
            ->when($end_date, function ($q) use ($end_date) {
                $q->whereDate('coupon_end_date', '<=', $end_date);
            });

        $coupons = retrieve_data($query, 'created_at', 'coupons');


        return response()->json($coupons, 200);
    }



    /**
     * @OA\Put(
     *      path="/v1.0/coupons/toggle-active",
     *      operationId="toggleActiveCoupon",
     *      tags={"coupon_management"},
     *      summary="Toggle coupon active status",
     *      description="Toggle the is_active flag of a coupon (requires coupon_update permission)",
     *      security={{"bearerAuth": {}}},
     *      @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"id"},
     *            @OA\Property(property="id", type="integer", example=1, description="Coupon id")
     *         )
     *      ),
     *      @OA\Response(response=200, description="Coupon status updated successfully"),
     *      @OA\Response(response=401, description="Unauthorized"),
     *      @OA\Response(response=404, description="Coupon not found"),
     *      @OA\Response(response=422, description="Validation Error")
     * )
     */
    public function toggleActiveCoupon(CouponToggleActiveRequest $request)
    {
        try {
           
     

           if (!auth()->user()->hasAnyRole(['owner', 'admin', 'lecturer'])) {
            return response()->json([
                "message" => "You can not perform this action"
            ], 401);
        }


            $data = $request->validated();
            $coupon = Coupon::find($data['id']);

            if (! $coupon) {
                return response()->json(['message' => 'Coupon not found'], 404);
            }

            $coupon->is_active = !$coupon->is_active;
            $coupon->save();

            return response()->json([
                'message' => 'Coupon status updated successfully',
                'coupon' => $coupon
            ], 200);
        } catch (\Exception $e) {
     return response()->json(['message' => $e->getMessage()], 500);
        }
    }



    /**
     * @OA\Delete(
     *      path="/v1.0/coupons/{garage_id}/{id}",
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
                return response()->json(['message' => 'Coupon not found'], 404);
            }

            $coupon->delete();

            return response()->json(['ok' => true], 200);
        } catch (\Exception $e) {
           
             return response()->json(['message' => $e->getMessage()], 500);
        }
    }



























































}
