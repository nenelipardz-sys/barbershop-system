<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="<?= e(csrf_hash()) ?>">
  <title><?= e($shopName) ?> · More than just a haircut</title>
  <link rel="icon" href="<?= asset('assets/img/logo-monogram.png') ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@500;600;700&family=Cormorant+Garamond:ital,wght@0,500;0,600;1,500;1,600&family=Inter:wght@400;500;600&family=Montserrat:wght@300;400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= asset('assets/css/landing.css') ?>">
  <script>document.documentElement.classList.add('js');</script>
</head>
<body class="landing" data-base="<?= e(BASE_URL) ?>">

<!-- ========== Fixed top bar ========== -->
<header class="lp-bar" id="lpBar">
  <button class="lp-menu-btn" type="button" data-menu-open aria-label="Open menu"><span>MENU</span><i></i></button>
  <a class="lp-logo" href="#top" aria-label="<?= e($shopName) ?>">
    <img src="<?= asset('assets/img/logo-monogram.png') ?>" alt="">
    <span><strong>LUMIÉ TALENT</strong><small>PARIS</small></span>
  </a>
  <a class="lp-book" href="<?= url('client/book') ?>">BOOK NOW</a>
</header>

<!-- ========== Slide-out menu ========== -->
<div class="lp-menu" id="lpMenu" aria-hidden="true">
  <div class="lp-menu-backdrop" data-menu-close></div>
  <nav class="lp-menu-panel" aria-label="Menu">
    <button class="lp-menu-close" type="button" data-menu-close aria-label="Close menu"><?= icon('x', '', 26) ?></button>
    <img class="lp-menu-logo" src="<?= asset('assets/img/logo-monogram.png') ?>" alt="">
    <a href="#services" data-menu-close>Our Services</a>
    <a href="#testimonials" data-menu-close>Testimonials</a>
    <a href="#visit" data-menu-close>Visit Us</a>
    <span class="lp-menu-sep"></span>
    <a href="<?= url('client/home') ?>">My Dashboard</a>
    <a href="<?= url('client/book') ?>">Book Appointment</a>
    <a href="<?= url('client/appointments') ?>">My Appointments</a>
    <a href="<?= url('client/history') ?>">Visit History</a>
    <a href="<?= url('client/profile') ?>">Profile</a>
    <a class="lp-menu-out" href="<?= url('logout?t=' . csrf_hash()) ?>">Log Out</a>
  </nav>
</div>

<main id="top">
  <!-- ========== 1. Hero (video) ========== -->
  <section class="lp-hero">
    <video class="lp-hero-video" id="heroVideo" autoplay muted loop playsinline preload="auto"
           poster="<?= asset('assets/img/landing/hero-poster.jpg') ?>">
      <source src="<?= url('assets/video/hero.mp4') ?>" type="video/mp4">
    </video>
    <div class="lp-hero-shade"></div>
    <div class="lp-hero-content">
      <p class="lp-kicker lp-in" style="--d:.1s">GENTLEMEN <span class="lp-kicker-ico"><?= icon('scissors', '', 22, 1.5) ?></span> ONLY</p>
      <h1 class="lp-hero-title lp-in" style="--d:.25s">LUMIÉ TALENT</h1>
      <p class="lp-hero-sub lp-in" style="--d:.4s"><small>THE</small> BARBER SHOP <small>FOR MEN</small></p>
      <p class="lp-hero-text lp-in" style="--d:.55s">At <?= e($shopName) ?>, a barber shop for gentlemen,<br>we are dedicated to giving you more than just a haircut — a confidence.</p>
      <div class="lp-hero-btns lp-in" style="--d:.7s">
        <a class="lp-btn lp-btn-gold" href="<?= url('client/book') ?>">MAKE AN APPOINTMENT</a>
        <a class="lp-btn lp-btn-ghost" href="#services">VIEW SERVICES</a>
      </div>
    </div>
    <button class="lp-sound" type="button" data-hero-sound aria-label="Turn sound on"><?= icon('play', '', 14) ?><span>SOUND OFF</span></button>
    <a class="lp-scroll" href="#about" aria-label="Scroll down"><span></span></a>
    <?= torn_edge(11, 'to-cream') ?>
  </section>

  <!-- ========== 2. It's more than a haircut ========== -->
  <section class="lp-cream lp-split" id="about">
    <div class="lp-prop lp-prop-left reveal" data-anim="left">
      <img src="<?= asset('assets/img/landing/craft.jpg') ?>" alt="A fresh, sharp haircut">
    </div>
    <div class="lp-copy reveal">
      <?= ornament() ?>
      <h2 class="lp-title">IT’S MORE THAN A HAIRCUT</h2>
      <?= ornament() ?>
      <p>We do this by providing high-quality grooming services for men in a warm, friendly atmosphere. We have removed
        all the hassle of getting a haircut — book online, choose your favorite barber, and walk in right on time.
        Relax with a cup of coffee, good music and great conversation, while we take care of the rest.
        And gentlemen, that’s just while you wait.</p>
      <a class="lp-brush lp-brush-navy" href="<?= url('client/book') ?>"><span>MAKE AN APPOINTMENT</span></a>
    </div>
    <?= torn_edge(23, 'to-navy') ?>
  </section>

  <!-- ========== 3. Our services (parallax) ========== -->
  <section class="lp-band lp-services" id="services" style="--bg:url('<?= asset('assets/img/landing/services-bg.jpg') ?>')">
    <div class="lp-band-inner">
      <div class="reveal">
        <?= ornament() ?>
        <h2 class="lp-title lp-title-light">OUR SERVICES</h2>
        <?= ornament() ?>
      </div>
      <div class="lp-svc-row">
        <?php foreach ($services as $i => $s): ?>
          <a class="lp-svc reveal" style="--d:<?= $i * 0.1 ?>s" href="<?= url('client/book?service=' . $s->id) ?>">
            <span class="lp-svc-ico"><?= landing_service_icon($s->name) ?></span>
            <strong><?= e($s->name) ?></strong>
            <small><?= peso($s->price) ?> · <?= (int) $s->duration ?> mins</small>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
    <?= torn_edge(37, 'to-cream') ?>
  </section>

  <!-- ========== 4. Testimonials (brush-framed videos + words) ========== -->
  <section class="lp-cream lp-testimonials" id="testimonials">
    <div class="lp-razor" aria-hidden="true">
      <svg viewBox="0 0 320 90">
        <defs>
          <linearGradient id="rzBlade" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#f4f4f2"/><stop offset=".5" stop-color="#b9bcc0"/><stop offset="1" stop-color="#7d8186"/></linearGradient>
          <linearGradient id="rzHandle" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#f1d891"/><stop offset=".55" stop-color="#c69a4d"/><stop offset="1" stop-color="#8f6a2c"/></linearGradient>
        </defs>
        <path d="M8 38 L186 22 L196 50 L22 58 Q6 56 8 38 Z" fill="url(#rzBlade)" stroke="#6d7177" stroke-width="1"/>
        <path d="M22 54 L192 44" stroke="#ffffff" stroke-opacity=".7" stroke-width="1.2"/>
        <circle cx="196" cy="37" r="6" fill="#3a3f46"/>
        <path d="M194 30 Q250 18 312 34 Q318 44 306 50 Q250 44 198 46 Z" fill="url(#rzHandle)" stroke="#7a5a24" stroke-width="1"/>
      </svg>
    </div>
    <div class="lp-head reveal">
      <?= ornament() ?>
      <h2 class="lp-title">WHAT OUR CLIENTS SAY</h2>
      <?= ornament() ?>
    </div>
    <div class="lp-frame reveal" data-anim="zoom">
      <svg class="lp-ink" viewBox="0 0 1000 620" preserveAspectRatio="none" aria-hidden="true">
        <defs>
          <filter id="ink" x="-10%" y="-10%" width="120%" height="120%">
            <feTurbulence type="fractalNoise" baseFrequency="0.022 0.06" numOctaves="4" seed="8" result="n"/>
            <feDisplacementMap in="SourceGraphic" in2="n" scale="38" xChannelSelector="R" yChannelSelector="G"/>
          </filter>
          <filter id="ink2" x="-10%" y="-10%" width="120%" height="120%">
            <feTurbulence type="fractalNoise" baseFrequency="0.05 0.012" numOctaves="3" seed="21" result="n"/>
            <feDisplacementMap in="SourceGraphic" in2="n" scale="30" xChannelSelector="G" yChannelSelector="R"/>
          </filter>
        </defs>
        <rect x="46" y="44" width="908" height="532" fill="none" stroke="#0c1a2e" stroke-width="64" filter="url(#ink)"/>
        <rect x="62" y="60" width="876" height="500" fill="none" stroke="#0e1d33" stroke-width="46" filter="url(#ink2)"/>
        <path d="M30 70 L120 40 M880 590 L975 560" stroke="#0c1a2e" stroke-width="18" stroke-linecap="round" filter="url(#ink2)"/>
      </svg>
      <div class="lp-frame-inner">
        <figure class="lp-tvid">
          <video data-tvid muted loop playsinline preload="metadata" poster="<?= asset('assets/img/landing/testimony-1.jpg') ?>">
            <source src="<?= url('assets/video/testimony-1.mp4') ?>" type="video/mp4">
          </video>
          <button class="lp-tvid-sound" type="button" data-tvid-sound aria-label="Turn sound on"><?= icon('play', '', 13) ?><span>Tap for sound</span></button>
          <figcaption>Client Story · Textured Crop</figcaption>
        </figure>

        <div class="lp-quotes" data-quotes>
          <span class="lp-quote-mark" aria-hidden="true">“</span>
          <?php foreach ($testimonials as $i => $t): ?>
            <blockquote class="lp-quote<?= $i === 0 ? ' active' : '' ?>">
              <div class="lp-stars" aria-label="5 out of 5 stars"><?= str_repeat(icon('star', '', 16), 5) ?></div>
              <p><?= e($t['quote']) ?></p>
              <footer>
                <?= avatar($t['name'], $t['avatar'], 46) ?>
                <span><strong><?= e($t['name']) ?></strong><small><?= e($t['service']) ?></small></span>
              </footer>
            </blockquote>
          <?php endforeach; ?>
          <div class="lp-dots">
            <?php foreach ($testimonials as $i => $t): ?>
              <button type="button" class="<?= $i === 0 ? 'active' : '' ?>" data-quote="<?= $i ?>" aria-label="Show review <?= $i + 1 ?>"></button>
            <?php endforeach; ?>
          </div>
        </div>

        <figure class="lp-tvid">
          <video data-tvid muted loop playsinline preload="metadata" poster="<?= asset('assets/img/landing/testimony-2.jpg') ?>">
            <source src="<?= url('assets/video/testimony-2.mp4') ?>" type="video/mp4">
          </video>
          <button class="lp-tvid-sound" type="button" data-tvid-sound aria-label="Turn sound on"><?= icon('play', '', 13) ?><span>Tap for sound</span></button>
          <figcaption>Client Story · Face-Shape Cut</figcaption>
        </figure>
      </div>
    </div>
  </section>

  <!-- ========== 5. Enjoy your haircut again ========== -->
  <section class="lp-cream lp-split lp-enjoy">
    <div class="lp-prop lp-prop-round reveal" data-anim="left">
      <img src="<?= asset('assets/img/landing/shave.jpg') ?>" alt="Straight-razor shave">
    </div>
    <div class="lp-copy reveal">
      <?= ornament() ?>
      <h2 class="lp-title">ENJOY YOUR HAIRCUT AGAIN</h2>
      <?= ornament() ?>
      <p>Your barber will take you to your own chair and make sure you are comfortable from start to finish. A precise cut,
        a hot soothing towel, a clean line-up and the finishing products that suit your hair — every detail is done
        the way you like it. We will do our best to earn your trust and make you want to come back,
        every time you need a haircut.</p>
      <a class="lp-brush lp-brush-navy" href="<?= url('client/book') ?>"><span>MAKE AN APPOINTMENT</span></a>
    </div>
    <?= torn_edge(51, 'to-navy') ?>
  </section>

  <!-- ========== 6. Join our team (parallax) ========== -->
  <section class="lp-band lp-team" style="--bg:url('<?= asset('assets/img/landing/team-bg.jpg') ?>')">
    <div class="lp-band-inner reveal">
      <?= ornament() ?>
      <h2 class="lp-title lp-title-light">JOIN OUR TEAM</h2>
      <?= ornament() ?>
      <p>Skilled with the clippers and passionate about the craft? We are always looking for talented barbers.</p>
      <a class="lp-brush lp-brush-gold" href="mailto:<?= e($email) ?>?subject=<?= rawurlencode('Barber application – ' . $shopName) ?>"><span>APPLY NOW</span></a>
    </div>
  </section>

  <!-- ========== 7. Stay in the know ========== -->
  <section class="lp-news">
    <div class="lp-news-copy reveal">
      <h2>STAY IN THE KNOW</h2>
      <p>Be the first to get updates on promos and events.</p>
    </div>
    <form class="lp-news-form reveal" data-subscribe novalidate>
      <input type="text" name="name" placeholder="Name" value="<?= e($user->getName()) ?>" aria-label="Name" required>
      <input type="email" name="email" placeholder="Email Address" value="<?= e($user->getEmail()) ?>" aria-label="Email address" required>
      <button type="submit">Subscribe Now</button>
      <p class="lp-news-msg" role="status"></p>
    </form>
  </section>
</main>

<!-- ========== 8. Footer ========== -->
<footer class="lp-footer" id="visit">
  <div class="lp-foot-grid">
    <div>
      <h3>SERVICES</h3>
      <?php foreach ($services as $s): ?><a href="<?= url('client/book?service=' . $s->id) ?>"><?= e($s->name) ?></a><?php endforeach; ?>
    </div>
    <div>
      <h3>VISIT US</h3>
      <p><?= icon('map-pin', '', 15) ?> <?= e($address) ?></p>
      <p><?= icon('clock', '', 15) ?> <?= e($hours) ?></p>
    </div>
    <div>
      <h3>INFORMATION</h3>
      <a href="<?= url('client/book') ?>">Book Appointment</a>
      <a href="<?= url('client/appointments') ?>">My Appointments</a>
      <a href="<?= url('client/history') ?>">Visit History</a>
      <a href="<?= url('client/favorites') ?>">Favorites</a>
    </div>
    <div>
      <h3>FOLLOW US</h3>
      <div class="lp-social">
        <a href="#" aria-label="Facebook"><svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M14 8h3V4h-3a4 4 0 0 0-4 4v2H8v4h2v8h4v-8h3l1-4h-4V8a1 1 0 0 1 1-1z"/></svg></a>
        <a href="#" aria-label="Instagram"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor"/></svg></a>
        <a href="#" aria-label="TikTok"><svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M16 3c.4 2.3 1.8 3.8 4 4v3.2c-1.5 0-2.9-.4-4-1.1V15a6 6 0 1 1-6-6v3.3A2.7 2.7 0 1 0 12.7 15V3z"/></svg></a>
      </div>
      <p><?= icon('phone', '', 15) ?> <?= e($phone) ?></p>
      <p><?= icon('mail', '', 15) ?> <?= e($email) ?></p>
    </div>
  </div>
  <div class="lp-foot-bottom">
    <img src="<?= asset('assets/img/logo-monogram.png') ?>" alt="">
    <span>© <?= date('Y') ?> <?= e($shopName) ?> · Cut &amp; Schedule</span>
  </div>
</footer>

<script src="<?= asset('assets/js/landing.js') ?>"></script>
</body>
</html>
