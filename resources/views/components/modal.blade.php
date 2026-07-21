@props([
    'show' => 'open',
    'title' => '',
    'maxWidth' => 'max-w-lg',
])

<div
    x-show="{{ $show }}"
    x-cloak
    class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6"
    style="display: none;"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
>
    <div
        class="absolute inset-0 bg-stone-900/50 backdrop-blur-sm"
        @click="{{ $show }} = false"
        aria-hidden="true"
    ></div>
    <div
        @click.stop
        class="relative z-10 w-full {{ $maxWidth }} overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-2xl transition-all duration-200"
        x-show="{{ $show }}"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
    >
        <div class="flex items-center justify-between gap-3 border-b border-gray-100 px-6 py-4">
            @if($title !== '')
                <h3 class="min-w-0 flex-1 text-lg font-semibold text-stone-900">{{ $title }}</h3>
            @else
                <span class="flex-1"></span>
            @endif
            <button
                type="button"
                class="rounded-lg p-1.5 text-slate-400 transition-all duration-200 hover:bg-gray-100 hover:text-slate-700 active:scale-95"
                @click="{{ $show }} = false"
                aria-label="Close"
            >
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="px-6 py-5">
            {{ $slot }}
        </div>
    </div>
</div>
