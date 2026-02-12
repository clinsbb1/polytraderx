@extends('layouts.public')

@section('title', 'Contact — PolyTraderX')

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
                            <p>support@polytraderx.com</p>
                        </div>
                    </div>

                    <div class="ptx-contact-item">
                        <div class="ptx-contact-item-icon"><i class="bi bi-telegram"></i></div>
                        <div>
                            <h6>Telegram</h6>
                            <p>Join our Telegram community for real-time support</p>
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
                        <a href="#" aria-label="Twitter"><i class="bi bi-twitter-x"></i></a>
                        <a href="#" aria-label="Telegram"><i class="bi bi-telegram"></i></a>
                        <a href="#" aria-label="Discord"><i class="bi bi-discord"></i></a>
                    </div>
                </div>

                {{-- Right: Contact Form --}}
                <div>
                    <div class="glass-card">
                        <h4 class="mb-4">Send a Message</h4>
                        <form>
                            <div class="mb-3">
                                <label class="ptx-label">Name</label>
                                <input type="text" class="ptx-input" placeholder="Your name">
                            </div>
                            <div class="mb-3">
                                <label class="ptx-label">Email</label>
                                <input type="email" class="ptx-input" placeholder="your@email.com">
                            </div>
                            <div class="mb-3">
                                <label class="ptx-label">Subject</label>
                                <select class="ptx-input">
                                    <option value="">Select a topic</option>
                                    <option value="support">Technical Support</option>
                                    <option value="billing">Billing Question</option>
                                    <option value="bug">Bug Report</option>
                                    <option value="feature">Feature Request</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="mb-4">
                                <label class="ptx-label">Message</label>
                                <textarea class="ptx-input" rows="5" placeholder="Tell us how we can help..."></textarea>
                            </div>
                            <button type="button" class="btn btn-ptx-primary w-100">Send Message</button>
                            <p class="text-center mt-3" style="color: var(--text-secondary); font-size: 0.8rem;">This form is for display only. Please email us directly at support@polytraderx.com.</p>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
