@props([
    'label',
    'name',
    'id',
    'autocomplete' => 'current-password',
])

@php
    $inputClass = 'w-full rounded-xl border border-slate-300 bg-slate-50/50 px-4 py-2.5 pr-11 text-slate-800 shadow-sm placeholder:text-slate-400 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200/80 focus:bg-white transition-colors';
@endphp

<div>
    <label for="{{ $id }}" class="mb-1.5 block text-sm font-medium text-slate-700">{{ $label }}</label>
    <div class="relative">
        <input
            type="password"
            name="{{ $name }}"
            id="{{ $id }}"
            autocomplete="{{ $autocomplete }}"
            {{ $attributes->merge(['class' => $inputClass]) }}
        />
        <button
            type="button"
            class="absolute right-1 top-1/2 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-lg text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-400 focus-visible:ring-offset-1"
            data-password-toggle
            data-target="{{ $id }}"
            aria-label="Нууц үг харуулах"
            aria-pressed="false"
            title="Нууц үг харуулах"
        >
            <span class="password-toggle-icon-show" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
            </span>
            <span class="password-toggle-icon-hide hidden" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                </svg>
            </span>
        </button>
    </div>
</div>

@once
    <script>
        (function () {
            function syncToggleState(btn, input) {
                const visible = input.type === 'text';
                btn.setAttribute('aria-pressed', visible ? 'true' : 'false');
                const hideLabel = 'Нууц үг нуулах';
                const showLabel = 'Нууц үг харуулах';
                btn.setAttribute('aria-label', visible ? hideLabel : showLabel);
                btn.setAttribute('title', visible ? hideLabel : showLabel);
                const showIcon = btn.querySelector('.password-toggle-icon-show');
                const hideIcon = btn.querySelector('.password-toggle-icon-hide');
                if (showIcon) {
                    showIcon.classList.toggle('hidden', visible);
                }
                if (hideIcon) {
                    hideIcon.classList.toggle('hidden', !visible);
                }
            }

            document.addEventListener('click', function (e) {
                const btn = e.target.closest('[data-password-toggle]');
                if (!btn || !document.contains(btn)) {
                    return;
                }
                const id = btn.getAttribute('data-target');
                const input = id ? document.getElementById(id) : null;
                if (!input) {
                    return;
                }
                input.type = input.type === 'password' ? 'text' : 'password';
                syncToggleState(btn, input);
            });
        })();
    </script>
@endonce
