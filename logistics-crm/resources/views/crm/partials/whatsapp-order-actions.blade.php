{{-- أزرار واتساب + تسجيل المتابعة عبر POST قبل فتح الرابط --}}
@inject('wa', \App\Services\WhatsApp\WhatsAppService::class)
@php
    $custUrl = $wa->customerWhatsAppUrl($order, $lateDays);
    $capUrl = $wa->captainWhatsAppUrl($order, $lateDays);
    $logUrl = route('crm.orders.log-followup', $order);
@endphp
<div class="flex flex-wrap items-center justify-end gap-2">
    @if($custUrl)
        <a href="{{ $custUrl }}"
           class="js-wa-log-followup inline-flex h-9 w-9 items-center justify-center rounded-full bg-emerald-500/15 text-emerald-600 ring-1 ring-emerald-500/30 transition hover:bg-emerald-500/25"
           title="واتساب للزبون — يُسجّل النظام متابعة اليوم تلقائياً عند النقر"
           aria-label="واتساب للزبون"
           data-log-url="{{ $logUrl }}">
            <i class="fa-brands fa-whatsapp text-lg" aria-hidden="true"></i>
        </a>
    @else
        <span class="inline-flex h-9 w-9 cursor-not-allowed items-center justify-center rounded-full bg-slate-100 text-slate-400 ring-1 ring-slate-200"
              title="لا يتوفر رقم هاتف للزبون — أضف «هاتف الزبون» على الطلب أو رقم المستخدم المرتبط">
            <i class="fa-brands fa-whatsapp text-lg" aria-hidden="true"></i>
        </span>
    @endif

    @if($capUrl)
        <a href="{{ $capUrl }}"
           class="js-wa-log-followup inline-flex h-9 w-9 items-center justify-center rounded-full bg-sky-500/15 text-sky-700 ring-1 ring-sky-500/30 transition hover:bg-sky-500/25"
           title="واتساب للكابتن — يُسجّل النظام متابعة اليوم تلقائياً عند النقر"
           aria-label="واتساب للكابتن"
           data-log-url="{{ $logUrl }}">
            <i class="fa-brands fa-whatsapp text-lg" aria-hidden="true"></i>
        </a>
    @else
        <span class="inline-flex h-9 w-9 cursor-not-allowed items-center justify-center rounded-full bg-slate-100 text-slate-400 ring-1 ring-slate-200"
              title="لا يتوفر كابتن مسند أو رقم هاتف للكابتن">
            <i class="fa-brands fa-whatsapp text-lg" aria-hidden="true"></i>
        </span>
    @endif
</div>
