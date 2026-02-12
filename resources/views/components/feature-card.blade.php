{{-- Individual feature card --}}
@props(['icon' => 'bi-star', 'title' => '', 'description' => ''])

<div class="glass-card h-100">
    <div class="ptx-feature-icon">
        <i class="bi {{ $icon }}"></i>
    </div>
    <h5>{{ $title }}</h5>
    <p style="color: var(--text-secondary); font-size: 0.95rem; margin-bottom: 0;">{{ $description }}</p>
</div>
