@php
    $enabled = (bool) config('services.turnstile.enabled', false);
    $siteKey = (string) config('services.turnstile.site_key', '');
@endphp

@if($enabled && $siteKey !== '')
    <div class="mb-3">
        <div class="cf-turnstile" data-sitekey="{{ $siteKey }}"></div>
        @error('cf-turnstile-response')
            <div class="ptx-input-error mt-2">{{ $message }}</div>
        @enderror
    </div>

    @once
        @push('scripts')
            <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
        @endpush
    @endonce
@endif
