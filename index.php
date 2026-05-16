<?php

require_once __DIR__ . '/Config/bootstrap.php';

render_header('PrimeCare Dental Clinic', ['body_class' => 'landing-body']);
$authMessage = flash('auth_message');
$authError = flash('auth_error');
$registerError = flash('register_error');
$registerOld = flash('register_old') ?? [];
$openLoginModal = (bool) $authError || isset($_GET['login_required']);
$openRegisterModal = isset($_GET['modal']) && $_GET['modal'] === 'register';
$user = current_user();
$featuredServices = [
    [
        'title' => 'Braces',
        'description' => 'Achieve a perfectly aligned, confident smile with our expert orthodontic care tailored to your unique needs.',
        'icon' => 'shield',
        'image' => 'Braces.jpg',
    ],
    [
        'title' => 'Teeth Whitening',
        'description' => 'Advanced laser whitening for a brighter, more confident smile.',
        'icon' => 'spark',
        'image' => 'Teeth whitening.jpg',
    ],
    [
        'title' => 'Dental Implants',
        'description' => 'Permanent, natural-looking tooth replacements with high success rates.',
        'icon' => 'smile',
        'image' => 'dental implant.jpeg',
    ],
];
$contactDetails = [
    ['icon' => 'fa-solid fa-location-dot', 'label' => 'Clinic Address', 'value' => 'APM Centrale, A. Soriano Ave, Cebu City, 6000 Cebu'],
    ['icon' => 'fa-solid fa-phone-volume', 'label' => 'Phone Number', 'value' => '0935 456 9153'],
    ['icon' => 'fa-solid fa-envelope', 'label' => 'Email Address', 'value' => 'primecaredental@gmail.com'],
    ['icon' => 'fa-regular fa-clock', 'label' => 'Clinic Hours', 'value' => 'Monday-Friday | 9:00 AM to 5:00 PM'],
];
?>
<div class="site-shell">
    <header class="site-header prime-nav" id="siteHeader">
        <div class="prime-nav-inner">
            <a class="brand-inline" href="<?= h(route_url()) ?>">
                <span class="logo-mark"><i class="fa-solid fa-tooth"></i></span>
                <span>
                    <strong>PrimeCare Dental Clinic</strong>
                    <small>Modern dental care</small>
                </span>
            </a>
            <button class="icon-button mobile-nav-toggle" type="button" data-nav-toggle aria-label="Toggle navigation"><i class="fa-solid fa-bars"></i></button>
            <nav class="site-nav prime-nav-links" id="siteNav">
                <a href="#services"><i class="fa-regular fa-star"></i><span>Services</span></a>
                <a href="#about"><i class="fa-regular fa-circle-user"></i><span>About</span></a>
                <button class="ghost-link nav-button-reset" type="button" data-login-open><i class="fa-solid fa-right-to-bracket"></i><span>Sign In</span></button>
            </nav>
        </div>
    </header>

    <section class="prime-hero">
        <div class="prime-hero-copy">
            <span class="prime-badge"><i class="fa-solid fa-sparkles"></i><span>Modern Dentistry, Personalized Care</span></span>
            <h1>Your Journey to a <em>Perfect Smile</em> Starts Here</h1>
            <p>At PrimeCare Dental Hub, we combine state-of-the-art technology with a gentle touch to provide the best oral healthcare in the city.</p>
            <div class="prime-hero-actions">
                <button class="button prime-primary-cta" type="button" data-booking-open><i class="fa-regular fa-calendar-check"></i><span>Book Appointment</span></button>
                <a class="button-ghost prime-secondary-cta" href="#services"><i class="fa-solid fa-briefcase-medical"></i><span>View Our Services</span></a>
            </div>
            <div class="prime-stats">
                <div>
                    <strong>15k+</strong>
                    <span>Patients Served</span>
                </div>
                <div>
                    <strong>12+</strong>
                    <span>Expert Dentists</span>
                </div>
                <div>
                    <strong>4.9/5</strong>
                    <span>User Rating</span>
                </div>
            </div>
        </div>
        <div class="prime-hero-visual">
            <div class="prime-photo-card prime-photo-one" style="--photo-image: url('<?= h(asset_url(rawurlencode('1st picture.jpg'))) ?>')"></div>
        </div>
    </section>

    <section id="services" class="prime-services">
        <div class="prime-section-heading">
            <h2>Our Specialized Services</h2>
            <p>Comprehensive dental care tailored to your specific needs, from routine checkups to complex surgeries.</p>
        </div>
        <div class="prime-service-grid">
            <?php foreach ($featuredServices as $service): ?>
                <article class="prime-service-card">
                    <div class="prime-service-image" style="--service-image: url('<?= h(asset_url(rawurlencode($service['image']))) ?>')"></div>
                    <div class="prime-service-body">
                        <div class="prime-service-icon <?= h($service['icon']) ?>">
                            <?php if ($service['icon'] === 'shield'): ?>
                                <i class="fa-solid fa-shield-heart"></i>
                            <?php elseif ($service['icon'] === 'spark'): ?>
                                <i class="fa-solid fa-wand-magic-sparkles"></i>
                            <?php else: ?>
                                <i class="fa-regular fa-face-smile"></i>
                            <?php endif; ?>
                        </div>
                        <h3><?= h($service['title']) ?></h3>
                        <p><?= h($service['description']) ?></p>
                        <a href="<?= h(route_url('booking.php')) ?>"><span>Learn More</span> <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section id="about" class="prime-about">
        <div class="prime-about-visual-wrap">
            <div class="prime-photo-card prime-photo-two" style="--photo-image: url('<?= h(asset_url(rawurlencode('dentist.jpg'))) ?>')"></div>
            <div class="prime-floating-card">
                <div class="prime-floating-icon"><i class="fa-regular fa-clock"></i></div>
                <div>
                    <strong>24/7 Care</strong>
                    <p>Emergency services available anytime.</p>
                </div>
            </div>
        </div>
        <div class="prime-about-copy">
            <h2>Compassionate Care Meets Advanced Technology</h2>
            <p>Founded in 2010, PrimeCare Dental has been a pillar of health in the community. We believe that everyone deserves a smile they love, and we're committed to making that a reality through affordable, expert care.</p>
            <ul class="prime-about-list">
                <li>Painless procedures using modern anesthesia</li>
                <li>State-of-the-art 3D imaging and diagnostics</li>
                <li>Personalized treatment plans for every patient</li>
                <li>Comfortable, stress-free clinic environment</li>
            </ul>
        </div>
    </section>

    <section class="prime-contact-section">
        <div class="prime-section-heading prime-contact-heading">
            <h2>Contact Information</h2>
            <p>Reach PrimeCare Dental Clinic easily for inquiries, appointment assistance, and visit planning.</p>
        </div>
        <div class="prime-contact-grid">
            <div class="prime-contact-cards">
                <?php foreach ($contactDetails as $detail): ?>
                    <article class="prime-contact-card">
                        <div class="prime-contact-icon"><i class="<?= h($detail['icon']) ?>"></i></div>
                        <div>
                            <span><?= h($detail['label']) ?></span>
                            <strong><?= h($detail['value']) ?></strong>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            <div class="prime-map-card">
                <iframe
                    title="PrimeCare Dental Clinic Location"
                    src="https://www.google.com/maps?q=APM%20Centrale%2C%20A.%20Soriano%20Ave%2C%20Cebu%20City%2C%206000%20Cebu&z=16&output=embed"
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"
                    allowfullscreen></iframe>
            </div>
        </div>
    </section>
</div>

<div class="auth-modal <?= $openLoginModal ? 'is-open' : '' ?>" id="loginModal" aria-hidden="<?= $openLoginModal ? 'false' : 'true' ?>">
    <div class="auth-backdrop" data-login-close></div>
    <div class="auth-dialog card">
        <button class="auth-close" type="button" data-login-close aria-label="Close login modal">&times;</button>
        <p class="eyebrow">Secure sign in</p>
        <h2 class="section-title auth-title-center">LOGIN</h2>
        <p class="section-copy auth-copy-center">Admins, reception staff, dentists, and patients use the same secure sign-in entry and land on role-based dashboards.</p>
        <?php if ($authMessage): ?><div class="flash"><?= h($authMessage) ?></div><?php endif; ?>
        <?php if ($authError): ?><div class="flash"><span class="error-text"><?= h($authError) ?></span></div><?php endif; ?>
        <form method="post" action="<?= h(route_url('Auth/login.php')) ?>" class="form-grid auth-form-grid">
            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
            <label class="field full">
                <span>Email</span>
                <input type="email" name="email" placeholder="admin@primecare.test" required>
            </label>
            <label class="field full">
                <span>Password</span>
                <div class="password-field-wrap">
                    <input type="password" name="password" placeholder="Enter your password" required data-password-input>
                    <button class="password-toggle auth-link-reset" type="button" data-password-toggle aria-label="Show password">
                        <span class="password-toggle-icon is-visible" aria-hidden="true">
                            <svg viewBox="0 0 24 24" focusable="false">
                                <path d="M12 5C6.5 5 2.1 8.4 1 12c1.1 3.6 5.5 7 11 7s9.9-3.4 11-7c-1.1-3.6-5.5-7-11-7Zm0 11.2A4.2 4.2 0 1 1 12 7.8a4.2 4.2 0 0 1 0 8.4Zm0-6.7a2.5 2.5 0 1 0 0 5.1 2.5 2.5 0 0 0 0-5.1Z"/>
                            </svg>
                        </span>
                        <span class="password-toggle-icon is-hidden" aria-hidden="true">
                            <svg viewBox="0 0 24 24" focusable="false">
                                <path d="m3.3 2 18.7 18.7-1.4 1.4-3.1-3.1A13.3 13.3 0 0 1 12 20C6.5 20 2.1 16.6 1 13c.6-2 2.1-3.8 4.1-5.1L1.9 4.7 3.3 2Zm8.7 6a4.2 4.2 0 0 1 4.2 4.2c0 .8-.2 1.5-.6 2.1l-5.7-5.7c.6-.4 1.3-.6 2.1-.6Zm0-3c5.5 0 9.9 3.4 11 7a10.8 10.8 0 0 1-4 5.2l-1.5-1.5A8.9 8.9 0 0 0 20.9 12c-1-2.7-4.5-5.3-8.9-5.3-1.4 0-2.8.3-4 .8L6.7 6.2A12.6 12.6 0 0 1 12 5Zm-7.7 7c1 2.7 4.5 5.3 8.9 5.3 1 0 2-.1 2.9-.4l-2-2a4.2 4.2 0 0 1-5.7-5.7l-2-2A8.7 8.7 0 0 0 4.3 12Z"/>
                            </svg>
                        </span>
                    </button>
                </div>
            </label>
            <div class="field full auth-inline-meta">
                <a class="auth-register-link" href="#">Forgot password?</a>
            </div>
            <div class="field full">
                <button class="button auth-submit" type="submit">Sign in</button>
            </div>
            <div class="field full auth-register-wrap">
                <p class="auth-helper-text">Don't have account?</p>
                <button type="button" class="auth-register-link auth-link-reset" data-register-open>Register Here</button>
            </div>
        </form>
    </div>
</div>

<?php render_booking_modal(route_url('?modal=booking')); ?>

<div class="auth-modal <?= $openRegisterModal ? 'is-open' : '' ?>" id="registerModal" aria-hidden="<?= $openRegisterModal ? 'false' : 'true' ?>">
    <div class="auth-backdrop" data-register-close></div>
    <div class="auth-dialog card">
        <button class="auth-close" type="button" data-register-close aria-label="Close registration modal">&times;</button>
        <p class="eyebrow">Create account</p>
        <h2 class="section-title">Registration Page</h2>
        <p class="section-copy">Create your patient account using the same PrimeCare design system and secure auth flow.</p>
        <?php if ($registerError): ?><div class="flash"><span class="error-text"><?= h($registerError) ?></span></div><?php endif; ?>
        <form method="post" action="<?= h(route_url('Auth/register.php')) ?>" class="form-grid auth-form-grid">
            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
            <label class="field full">
                <span>Full Name</span>
                <input type="text" name="full_name" value="<?= h($registerOld['full_name'] ?? '') ?>" required>
            </label>
            <label class="field full">
                <span>Email Address</span>
                <input type="email" name="email" value="<?= h($registerOld['email'] ?? '') ?>" required>
            </label>
            <label class="field full">
                <span>Username</span>
                <input type="text" name="username" value="<?= h($registerOld['username'] ?? '') ?>" required>
            </label>
            <label class="field full">
                <span>Contact Number</span>
                <input type="text" name="contact_number" value="<?= h($registerOld['contact_number'] ?? '') ?>" maxlength="11" inputmode="numeric" pattern="\d{1,11}" required>
            </label>
            <label class="field full">
                <span>Password</span>
                <div class="password-field-wrap">
                    <input type="password" name="password" placeholder="Enter your password" required data-password-input>
                    <button class="password-toggle auth-link-reset" type="button" data-password-toggle aria-label="Show password">
                        <span class="password-toggle-icon is-visible" aria-hidden="true">
                            <svg viewBox="0 0 24 24" focusable="false">
                                <path d="M12 5C6.5 5 2.1 8.4 1 12c1.1 3.6 5.5 7 11 7s9.9-3.4 11-7c-1.1-3.6-5.5-7-11-7Zm0 11.2A4.2 4.2 0 1 1 12 7.8a4.2 4.2 0 0 1 0 8.4Zm0-6.7a2.5 2.5 0 1 0 0 5.1 2.5 2.5 0 0 0 0-5.1Z"/>
                            </svg>
                        </span>
                        <span class="password-toggle-icon is-hidden" aria-hidden="true">
                            <svg viewBox="0 0 24 24" focusable="false">
                                <path d="m3.3 2 18.7 18.7-1.4 1.4-3.1-3.1A13.3 13.3 0 0 1 12 20C6.5 20 2.1 16.6 1 13c.6-2 2.1-3.8 4.1-5.1L1.9 4.7 3.3 2Zm8.7 6a4.2 4.2 0 0 1 4.2 4.2c0 .8-.2 1.5-.6 2.1l-5.7-5.7c.6-.4 1.3-.6 2.1-.6Zm0-3c5.5 0 9.9 3.4 11 7a10.8 10.8 0 0 1-4 5.2l-1.5-1.5A8.9 8.9 0 0 0 20.9 12c-1-2.7-4.5-5.3-8.9-5.3-1.4 0-2.8.3-4 .8L6.7 6.2A12.6 12.6 0 0 1 12 5Zm-7.7 7c1 2.7 4.5 5.3 8.9 5.3 1 0 2-.1 2.9-.4l-2-2a4.2 4.2 0 0 1-5.7-5.7l-2-2A8.7 8.7 0 0 0 4.3 12Z"/>
                            </svg>
                        </span>
                    </button>
                </div>
            </label>
            <div class="field full">
                <button class="button auth-submit" type="submit">Create account</button>
            </div>
            <div class="field full auth-register-wrap">
                <button type="button" class="auth-register-link auth-link-reset" data-login-open>Already have an account? Sign in</button>
            </div>
        </form>
    </div>
</div>

<?php render_footer();
