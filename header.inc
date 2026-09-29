<!-- Header Navigation -->
<header class="fixed top-0 w-full z-50 bg-surface-container-lowest/90 backdrop-blur-md shadow-[0_1px_8px_rgba(0,0,0,0.04)] border-b border-outline-variant/20">
  <div class="h-20 w-full px-md md:px-lg flex items-center justify-between mx-auto max-w-container-max">
    <!-- Logo & Nama Perusahaan -->
    <a href="index.html#home" class="flex items-center gap-xs sm:gap-sm">
      <img alt="BEST Logistics Logo" class="h-12 sm:h-16 w-auto object-contain" src="assets/images/logo/best_logo2.png" />
      <span class="font-headline-sm text-sm sm:text-headline-sm text-primary uppercase tracking-tight font-bold"> Berkat Subuh Transpor </span>
    </a>

    <!-- Navigasi Desktop -->
    <nav class="hidden lg:flex items-center gap-lg" id="main-nav">
      <a class="nav-link font-body-sm text-body-sm text-on-surface-variant hover:text-primary transition-colors" href="index.html#home" data-i18n="nav.home">Home</a>
      <a class="nav-link font-body-sm text-body-sm text-on-surface-variant hover:text-primary transition-colors" href="index.html#about" data-i18n="nav.about">About Us</a>
      <a class="nav-link font-body-sm text-body-sm text-on-surface-variant hover:text-primary transition-colors" href="index.html#services" data-i18n="nav.services"
        >Services</a
      >
      <a class="nav-link font-body-sm text-body-sm text-on-surface-variant hover:text-primary transition-colors" href="index.html#fleet" data-i18n="nav.fleet">Fleet</a>
      <a class="nav-link font-body-sm text-body-sm text-on-surface-variant hover:text-primary transition-colors" href="index.html#projects" data-i18n="nav.projects"
        >Projects</a
      >
      <a class="nav-link font-body-sm text-body-sm text-on-surface-variant hover:text-primary transition-colors" href="index.html#contact" data-i18n="nav.contact">Contact</a>
      <a
        class="nav-link font-body-sm text-body-sm text-on-surface-variant hover:text-primary transition-colors font-bold text-primary"
        href="tracking.html"
        data-i18n="nav.tracking"
        >Tracking</a
      >
    </nav>

    <!-- Area Kanan Header (Toggle Bahasa, Profile, & Hamburger) -->
    <div class="flex items-center gap-sm md:gap-md">
      <!-- Tombol Switcher ID / EN -->
      <div class="flex items-center bg-surface-container-high rounded-full p-1 border border-outline-variant/30 text-xs font-bold text-on-surface-variant">
        <button onclick="setLanguage('id')" id="btn-lang-id" class="px-2.5 py-1 rounded-full transition-all hover:text-primary">ID</button>
        <span class="text-outline-variant opacity-40">|</span>
        <button onclick="setLanguage('en')" id="btn-lang-en" class="px-2.5 py-1 rounded-full transition-all hover:text-primary">EN</button>
      </div>

      <!-- Icon Profile -->

      <!-- Tombol Hamburger Menu -->
      <button id="mobile-menu-btn" class="lg:hidden p-1.5 text-on-surface hover:text-primary focus:outline-none" aria-label="Buka Menu">
        <span class="material-symbols-outlined text-[28px]">menu</span>
      </button>
    </div>
  </div>

  <!-- Dropdown Navigasi Mobile -->
  <div
    id="mobile-nav"
    class="hidden lg:hidden bg-surface-container-lowest border-t border-outline-variant/20 px-lg py-md flex flex-col gap-xs shadow-xl animate-[fadeIn_0.2s_ease-in-out]">
    <a class="py-sm border-b border-outline-variant/10 font-body-md text-on-surface hover:text-primary" href="index.html#home" data-i18n="nav.home">Home</a>
    <a class="py-sm border-b border-outline-variant/10 font-body-md text-on-surface hover:text-primary" href="index.html#about" data-i18n="nav.about">About Us</a>
    <a class="py-sm border-b border-outline-variant/10 font-body-md text-on-surface hover:text-primary" href="index.html#services" data-i18n="nav.services">Services</a>
    <a class="py-sm border-b border-outline-variant/10 font-body-md text-on-surface hover:text-primary" href="index.html#fleet" data-i18n="nav.fleet">Fleet</a>
    <a class="py-sm border-b border-outline-variant/10 font-body-md text-on-surface hover:text-primary" href="index.html#projects" data-i18n="nav.projects">Projects</a>
    <a class="py-sm border-b border-outline-variant/10 font-body-md text-on-surface hover:text-primary" href="index.html#contact" data-i18n="nav.contact">Contact</a>
    <a class="py-sm font-body-md text-primary font-bold flex items-center gap-xs" href="tracking.html">
      <span class="material-symbols-outlined text-[18px]">radar</span> <span data-i18n="nav.tracking_cargo">Tracking Cargo</span>
    </a>
  </div>
</header>
