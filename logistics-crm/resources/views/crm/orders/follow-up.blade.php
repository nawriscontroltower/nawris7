@extends('crm.layout')

@section('title', 'قائمة المتابعة')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-900">قائمة المتابعة</h1>
        <p class="mt-1 max-w-3xl text-sm leading-relaxed text-slate-600">
            تظهر هنا الطلبات غير المكتملة التي تجاوزت موعد التسليم بأكثر من <strong>ثلاثة أيام تقويمية كاملة</strong>
            (أي تأخير يعادل 4 أيام أو أكثر وفق احتساب النظام)، ليتم التواصل مع العميل أو إعادة التوزيع.
        </p>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-right text-sm">
                <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 font-semibold text-slate-700">المرجع</th>
                    <th class="px-4 py-3 font-semibold text-slate-700">الحالة</th>
                    <th class="px-4 py-3 font-semibold text-slate-700">الكابتن</th>
                    <th class="px-4 py-3 font-semibold text-slate-700">موعد التسليم</th>
                    <th class="px-4 py-3 font-semibold text-slate-700">أيام التأخير</th>
                    <th class="px-4 py-3 font-semibold text-slate-700" title="زبون (أخضر) / كابتن (أزرق) — يُسجّل النظام متابعة اليوم عند النقر">واتساب</th>
                    <th class="px-4 py-3 font-semibold text-slate-700">ملاحظات المتابعة</th>
                    <th class="px-4 py-3 font-semibold text-slate-700">حفظ</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                @forelse($orders as $order)
                    @php
                        $lateDays = $order->promised_delivery_at
                            ? (int) $order->promised_delivery_at->diffInDays(now())
                            : 0;
                    @endphp
                    <tr class="align-top hover:bg-slate-50/80">
                        <td class="px-4 py-3 font-mono font-medium">{{ $order->reference }}</td>
                        <td class="px-4 py-3">@include('crm.partials.order-status', ['status' => $order->status])</td>
                        <td class="px-4 py-3 text-slate-700">{{ $order->captain?->full_name ?? '—' }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $order->promised_delivery_at?->translatedFormat('Y/m/d H:i') ?? '—' }}</td>
                        <td class="px-4 py-3 font-bold text-red-700">{{ $lateDays }}</td>
                        <td class="px-4 py-3">@include('crm.partials.whatsapp-order-actions', ['order' => $order, 'lateDays' => $lateDays])</td>
                        <td class="px-4 py-3 min-w-[14rem]">
                            <form id="fu-{{ $order->id }}" action="{{ route('crm.orders.follow-up.update', $order) }}" method="post" class="space-y-2">
                                @csrf
                                <textarea name="follow_up_notes" rows="2"
                                          class="w-full rounded-lg border border-slate-300 px-2 py-1.5 text-xs focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900"
                                          placeholder="اتصال، وعد بالتسليم، تصعيد...">{{ old('follow_up_notes', $order->follow_up_notes) }}</textarea>
                                @error('follow_up_notes')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                                @if($order->last_follow_up_at)
                                    <p class="text-[10px] text-slate-500">آخر تحديث: {{ $order->last_follow_up_at->translatedFormat('Y/m/d H:i') }}</p>
                                @endif
                            </form>
                        </td>
                        <td class="px-4 py-3">
                            <button type="submit" form="fu-{{ $order->id }}"
                                    class="rounded-lg bg-slate-900 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-800">
                                حفظ المتابعة
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-10 text-center text-slate-500">لا توجد طلبات في قائمة المتابعة حالياً.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($orders->hasPages())
            <div class="border-t border-slate-100 px-4 py-3">{{ $orders->links() }}</div>
        @endif
    </div>
@endsection
