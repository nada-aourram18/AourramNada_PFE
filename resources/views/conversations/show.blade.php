@extends('layouts.app')

@section('title', 'Conversation #'.$conversation->id)
@section('heading', 'Conversation #'.$conversation->id)
@section('subheading', ($conversation->patient?->full_name ?? 'Anonyme').' · '.strtoupper($conversation->language))

@section('content')
<div class="mb-6 flex justify-end">
    <a href="{{ route('conversations.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition-all duration-200 hover:bg-gray-50 active:scale-[0.98]">Retour</a>
</div>

<div class="mx-auto max-w-3xl rounded-2xl border border-gray-100 bg-slate-100/80 p-4 shadow-inner sm:p-6" dir="ltr">
    <div class="space-y-3">
        @foreach($conversation->messages ?? [] as $m)
            @php $role = $m['role'] ?? 'assistant'; @endphp
            <div class="flex {{ $role === 'user' ? 'justify-end' : 'justify-start' }}">
                <div
                    dir="auto"
                    class="max-w-[85%] rounded-2xl px-4 py-2.5 text-sm shadow-md transition-all duration-200 {{ $role === 'user' ? 'rounded-br-md bg-teal-600 text-white' : 'rounded-bl-md border border-gray-100 bg-white text-slate-900' }}"
                >
                    <div class="whitespace-pre-wrap leading-relaxed">{{ $m['content'] ?? '' }}</div>
                    <div class="mt-1 text-[10px] font-medium opacity-70">{{ $m['timestamp'] ?? '' }}</div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
