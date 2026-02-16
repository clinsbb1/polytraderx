@extends('layouts.public')

@section('title', 'Contact — PolyTraderX')
@section('meta_description', 'Contact PolyTraderX support for technical issues, billing questions, and feature feedback. Advanced and Lifetime subscribers can use priority support form access.')

@section('content')
    <section class="ptx-section" style="padding-top: 140px;">
        <div class="container" style="max-width: 1000px;">
            <div class="ptx-contact-split">
                {{-- Left: Info --}}
                <div class="ptx-contact-info">
                    <h1><span class="ptx-gradient-text">Get in Touch</span></h1>
                    <p>Have a question, need support, or want to report a bug? We're here to help.</p>

                    <div class="ptx-contact-item">
                        <div class="ptx-contact-item-icon"><i class="bi bi-envelope"></i></div>
                        <div>
                            <h6>Email Support</h6>
                            <p>support@polytraderx.xyz</p>
                        </div>
                    </div>

                    <div class="ptx-contact-item">
                        <div class="ptx-contact-item-icon"><i class="bi bi-telegram"></i></div>
                        <div>
                            <h6>Telegram</h6>
                            <p><a href="https://t.me/+-DflYmPAmAowY2Q0" target="_blank" rel="noopener noreferrer">Join our Telegram community</a> for real-time support</p>
                        </div>
                    </div>

                    <div class="ptx-contact-item">
                        <div class="ptx-contact-item-icon"><i class="bi bi-clock"></i></div>
                        <div>
                            <h6>Response Time</h6>
                            <p>We aim to respond within 24 hours</p>
                        </div>
                    </div>

                    <div class="ptx-contact-item">
                        <div class="ptx-contact-item-icon"><i class="bi bi-bug"></i></div>
                        <div>
                            <h6>Bug Reports</h6>
                            <p>Include your Account ID, description, and screenshots</p>
                        </div>
                    </div>

                    <div class="ptx-social-icons mt-4">
                        <a href="https://x.com/polytraderx" target="_blank" rel="noopener noreferrer" aria-label="Twitter / X"><i class="bi bi-twitter-x"></i></a>
                        <a href="https://t.me/+-DflYmPAmAowY2Q0" target="_blank" rel="noopener noreferrer" aria-label="Telegram"><i class="bi bi-telegram"></i></a>
                        <a href="#" aria-label="Discord"><i class="bi bi-discord"></i></a>
                    </div>
                </div>

                {{-- Right: Contact Form --}}
                <div>
                    <div class="glass-card">
                        <h4 class="mb-4">Support Contact</h4>

                        @if (session('success'))
                            <div style="background: rgba(0,230,118,0.1); border: 1px solid rgba(0,230,118,0.2); border-radius: var(--radius-sm); padding: 12px; margin-bottom: 16px; color: var(--profit); font-size: 0.9rem;">
                                {{ session('success') }}
                            </div>
                        @endif

                        @if (session('error'))
                            <div style="background: rgba(255,71,87,0.1); border: 1px solid rgba(255,71,87,0.25); border-radius: var(--radius-sm); padding: 12px; margin-bottom: 16px; color: #ff7583; font-size: 0.9rem;">
                                {{ session('error') }}
                            </div>
                        @endif

                        @if($canSubmitContact)
                            <form method="POST" action="{{ route('contact.submit') }}" enctype="multipart/form-data">
                                @csrf

                                <div class="mb-3">
                                    <label class="ptx-label">Name</label>
                                    <input type="text" class="ptx-input" value="{{ auth()->user()->name }}" readonly>
                                </div>
                                <div class="mb-3">
                                    <label class="ptx-label">Email</label>
                                    <input type="email" class="ptx-input" value="{{ auth()->user()->email }}" readonly>
                                </div>
                                <div class="mb-3">
                                    <label class="ptx-label">Subject</label>
                                    <select class="ptx-input" name="subject" required>
                                        <option value="">Select a topic</option>
                                        <option value="support" {{ old('subject') === 'support' ? 'selected' : '' }}>Technical Support</option>
                                        <option value="billing" {{ old('subject') === 'billing' ? 'selected' : '' }}>Billing Question</option>
                                        <option value="bug" {{ old('subject') === 'bug' ? 'selected' : '' }}>Bug Report</option>
                                        <option value="feature" {{ old('subject') === 'feature' ? 'selected' : '' }}>Feature Request</option>
                                        <option value="other" {{ old('subject') === 'other' ? 'selected' : '' }}>Other</option>
                                    </select>
                                    @error('subject')
                                        <div class="ptx-input-error">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="mb-3">
                                    <label class="ptx-label">Screenshot (optional)</label>
                                    <input type="file" class="ptx-input" name="screenshot" accept=".jpg,.jpeg,.png,.webp,.gif,image/*">
                                    <div style="color: var(--text-secondary); font-size: 0.8rem; margin-top: 6px;">Upload an image to describe the issue (max 5MB).</div>
                                    @error('screenshot')
                                        <div class="ptx-input-error">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="mb-4">
                                    <label class="ptx-label">Message</label>
                                    <textarea class="ptx-input" name="message" rows="6" placeholder="Tell us how we can help..." required>{{ old('message') }}</textarea>
                                    @error('message')
                                        <div class="ptx-input-error">{{ $message }}</div>
                                    @enderror
                                </div>
                                <button type="submit" class="btn btn-ptx-primary w-100">Send Message</button>
                            </form>
                        @else
                            <p style="color: var(--text-secondary); margin-bottom: 0;">
                                The priority support form is available only for <strong>Advanced</strong> and <strong>Early Bird Lifetime</strong> subscribers.
                                Please email <a href="mailto:support@polytraderx.xyz">support@polytraderx.xyz</a> for assistance.
                            </p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
