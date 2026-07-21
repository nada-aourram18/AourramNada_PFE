<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('messages.chat.title') }} — {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['"Plus Jakarta Sans"', 'system-ui', 'sans-serif'] },
                    colors: { medical: { DEFAULT: '#0d9488', dark: '#0f766e', light: '#ccfbf1' } },
                },
            },
        };
    </script>
    <style>body { font-family: "Plus Jakarta Sans", system-ui, sans-serif; }</style>
</head>
<body class="min-h-screen bg-black text-white">
<div class="mx-auto flex min-h-screen max-w-lg flex-col px-3 py-6 sm:px-4">
    <div class="overflow-hidden rounded-3xl border border-gray-700 bg-gray-800 shadow-2xl">
        <header class="flex items-center justify-between gap-3 border-b border-gray-600 bg-gradient-to-r from-black to-gray-800 px-4 py-4 sm:px-5">
            <div class="flex items-center gap-3">
                <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-medical text-2xl shadow-inner ring-1 ring-white/20" aria-hidden="true">🏥</span>
                <div>
                    <h1 class="text-base font-bold tracking-tight text-white">Réceptionniste IA</h1>
                    <p class="text-xs font-medium text-gray-300">{{ __('messages.chat.subtitle') }}</p>
                </div>
            </div>
            <select id="ui-lang" class="rounded-xl border border-gray-600 bg-gray-700 px-2.5 py-2 text-xs font-semibold text-white backdrop-blur-sm focus:border-teal-400 focus:outline-none">
                <option value="fr">FR</option>
                <option value="en">EN</option>
            </select>
        </header>

        <div id="thread" class="space-y-3 overflow-y-auto bg-gray-800 p-4" style="min-height: 60vh; max-height: 60vh;"></div>

        <div id="typing" class="hidden border-t border-gray-600 bg-gray-700 px-4 py-2">
            <div class="flex items-center gap-1.5 text-xs font-medium text-gray-300">
                <span>{{ __('messages.chat.typing') }}</span>
                <span class="flex gap-0.5">
                    <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-teal-400" style="animation-delay: 0ms"></span>
                    <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-teal-400" style="animation-delay: 150ms"></span>
                    <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-teal-400" style="animation-delay: 300ms"></span>
                </span>
            </div>
        </div>

        <form id="chat-form" class="flex gap-2 border-t border-gray-600 bg-gray-700 p-3 sm:p-4">
            @csrf
            <input type="hidden" name="conversation_id" id="conversation_id" value="">
            <input type="hidden" name="language" id="language" value="{{ app()->getLocale() }}">
            <input
                id="message"
                name="message"
                required
                autocomplete="off"
                class="min-w-0 flex-1 rounded-2xl border border-gray-600 bg-gray-800 px-4 py-3 text-sm text-white shadow-inner transition-all duration-200 placeholder:text-gray-400 focus:border-teal-400 focus:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-teal-500/25"
                placeholder="{{ __('messages.chat.placeholder') }}"
            >
            <button
                type="submit"
                class="inline-flex shrink-0 items-center justify-center rounded-2xl bg-medical px-5 py-3 text-sm font-bold text-white shadow-lg shadow-medical/35 transition-all duration-200 hover:bg-medical-dark disabled:cursor-not-allowed disabled:opacity-50 active:scale-95"
                id="send-btn"
            >{{ __('messages.chat.send') }}</button>
        </form>
    </div>
    <p class="mt-4 text-center text-[11px] text-gray-500">{{ config('app.name') }} — expérience patient sécurisée</p>
</div>
<script>
    const thread = document.getElementById('thread');
    const form = document.getElementById('chat-form');
    const input = document.getElementById('message');
    const typing = document.getElementById('typing');
    const btn = document.getElementById('send-btn');
    const conv = document.getElementById('conversation_id');
    const lang = document.getElementById('language');
    const uiLang = document.getElementById('ui-lang');
    const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    function bubble(text, side) {
        const wrap = document.createElement('div');
        wrap.className = side === 'right' ? 'flex justify-end' : 'flex justify-start';
        const b = document.createElement('div');
        b.setAttribute('dir', 'auto');
        b.className = side === 'right'
            ? 'max-w-[88%] rounded-2xl rounded-br-md bg-medical px-4 py-2.5 text-sm font-medium leading-relaxed text-white shadow-md'
            : 'max-w-[88%] rounded-2xl rounded-bl-md border border-gray-600 bg-gray-700 px-4 py-2.5 text-sm leading-relaxed text-white shadow-sm';
        b.textContent = text;
        wrap.appendChild(b);
        thread.appendChild(wrap);
        thread.scrollTop = thread.scrollHeight;
    }

    uiLang.value = @json(app()->getLocale());
    uiLang.addEventListener('change', () => {
        lang.value = uiLang.value;
        window.location.href = '/locale/' + uiLang.value;
    });

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const text = input.value.trim();
        if (!text) return;
        bubble(text, 'right');
        input.value = '';
        typing.classList.remove('hidden');
        btn.disabled = true;
        try {
            const res = await fetch(@json(route('chat.send')), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify({
                    message: text,
                    conversation_id: conv.value || null,
                    language: lang.value,
                }),
            });
            const data = await res.json();
            if (!data.ok) throw new Error(data.error || 'Erreur');
            conv.value = data.conversation_id || '';
            bubble(data.reply || '', 'left');
        } catch (err) {
            bubble(err.message || 'Erreur', 'left');
        } finally {
            typing.classList.add('hidden');
            btn.disabled = false;
        }
    });
</script>
</body>
</html>
