<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateOrderFollowUpRequest;
use App\Models\Order;
use App\Services\Orders\LateOrderQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(): View
    {
        $orders = Order::query()
            ->with(['captain:id,code,full_name,phone', 'user:id,name,phone'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('crm.orders.index', compact('orders'));
    }

    public function followUpQueue(LateOrderQuery $lateOrderQuery): View
    {
        $orders = $lateOrderQuery->followUpQueue()->paginate(20);

        return view('crm.orders.follow-up', compact('orders'));
    }

    public function updateFollowUp(UpdateOrderFollowUpRequest $request, Order $order): RedirectResponse
    {
        $validated = $request->validated();

        $order->update([
            'follow_up_notes' => $validated['follow_up_notes'] ?? null,
            'last_follow_up_at' => now(),
        ]);

        return redirect()
            ->route('crm.orders.follow-up')
            ->with('success', 'تم حفظ ملاحظات المتابعة.');
    }

    /**
     * تسجيل متابعة تشغيلية (يحدّث وقت آخر متابعة) — يُستدعى قبل فتح واتساب.
     */
    public function logFollowUp(Order $order): JsonResponse
    {
        $order->forceFill(['last_follow_up_at' => now()])->save();

        return response()->json([
            'success' => true,
            'last_follow_up_at' => $order->last_follow_up_at?->toIso8601String(),
        ]);
    }
}
