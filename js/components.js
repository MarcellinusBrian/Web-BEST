document.addEventListener("DOMContentLoaded", () => {
  // Load Header Component
  const headerPlaceholder = document.getElementById("header-placeholder");
  if (headerPlaceholder) {
    fetch("header.inc", { cache: "no-store" }) // Ubah ke "header.inc" jika Anda menggunakan ekstensi .inc
      .then((res) => res.text())
      .then((data) => {
        headerPlaceholder.innerHTML = cleanInjectedScripts(data);
        initNavState();
        initMobileMenu();
      })
      .catch((err) => console.error("Error loading header:", err));
  }

  // Load Footer Component
  const footerPlaceholder = document.getElementById("footer-placeholder");
  if (footerPlaceholder) {
    fetch("footer.inc", { cache: "no-store" })
      .then((res) => res.text())
      .then((data) => {
        footerPlaceholder.innerHTML = cleanInjectedScripts(data);
      })
      .catch((err) => console.error("Error loading footer:", err));
  }
});

function cleanInjectedScripts(htmlString) {
  const parser = new DOMParser();
  const doc = parser.parseFromString(htmlString, "text/html");
  doc.querySelectorAll("script").forEach((script) => script.remove());
  return doc.body.innerHTML || htmlString;
}

function initNavState() {
  const currentPath = window.location.pathname.split("/").pop() || "index.html";
  const navLinks = document.querySelectorAll("header nav a");

  if (navLinks.length === 0) return;

  if (currentPath !== "index.html" && currentPath !== "") {
    navLinks.forEach((link) => {
      const href = link.getAttribute("href");
      if (href && href.includes(currentPath)) {
        setLinkActive(link);
      } else {
        setLinkInactive(link);
      }
    });
    return;
  }

  initScrollSpy(navLinks);
}

function setLinkActive(link) {
  link.classList.add("text-primary", "font-bold");
  link.classList.remove("text-on-surface-variant");
}

function setLinkInactive(link) {
  link.classList.remove("text-primary", "font-bold");
  link.classList.add("text-on-surface-variant");
}

function initScrollSpy(navLinks) {
  const sections = document.querySelectorAll("section[id]");

  const observerOptions = {
    root: null,
    // -96px di atas disesuaikan dengan scroll-padding-top CSS kamu
    rootMargin: "-96px 0px -50% 0px",
    threshold: 0.1,
  };

  const observer = new IntersectionObserver((entries) => {
    // Jika masih di paling atas halaman (Home)
    if (window.scrollY < 100) {
      navLinks.forEach((link) => {
        const href = link.getAttribute("href");
        if (href === "index.html" || href === "#" || href?.endsWith("index.html#home") || href === "#home") {
          setLinkActive(link);
        } else {
          setLinkInactive(link);
        }
      });
      return;
    }

    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        const id = entry.target.getAttribute("id");

        navLinks.forEach((link) => {
          const href = link.getAttribute("href");
          if (href && (href.endsWith("#" + id) || href === "#" + id)) {
            setLinkActive(link);
          } else {
            setLinkInactive(link);
          }
        });
      }
    });
  }, observerOptions);

  sections.forEach((section) => observer.observe(section));
}

// FUNGSI TOGGLE HAMBURGER MENU MOBILE
function initMobileMenu() {
  const menuBtn = document.getElementById("mobile-menu-btn");
  const mobileNav = document.getElementById("mobile-nav");

  if (menuBtn && mobileNav) {
    menuBtn.addEventListener("click", () => {
      mobileNav.classList.toggle("hidden");
    });

    // Otomatis tutup menu saat link diklik
    mobileNav.querySelectorAll("a").forEach((link) => {
      link.addEventListener("click", () => {
        mobileNav.classList.add("hidden");
      });
    });
  }
}
