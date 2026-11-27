@if (session('status'))
    <div role="status" class="rounded-xl bg-emerald-100 px-4 py-3 text-sm font-semibold text-emerald-900">{{ session('status') }}</div>
@endif
@if (session('error'))
    <div role="alert" class="rounded-xl bg-red-100 px-4 py-3 text-sm font-semibold text-red-800">{{ session('error') }}</div>
@endif
