    /* ---------- Collapsible submenus ---------- */
    function toggleSubmenu(id) {
      const submenu = document.getElementById(id);
      const arrow = document.getElementById(`arrow-${id}`);
      submenu.classList.toggle("active");
      arrow.classList.toggle("rotate-180");
      // Optional: remember state
      const isOpen = submenu.classList.contains("active");
      localStorage.setItem(`submenu:${id}`, isOpen ? "1" : "0");
    }

    // Restore submenu states on load
    ["inventoryMenu","stock_menue","customersMenu","boutiquesMenu","tailorMenu","specialordersMenu","posMenu"].forEach(id => {
      const saved = localStorage.getItem(`submenu:${id}`);
      if (saved === "1") {
        document.getElementById(id)?.classList.add("active");
        document.getElementById(`arrow-${id}`)?.classList.add("rotate-180");
      }
    });

    /* ---------- Dropdown helpers (click-to-toggle + click-outside) ---------- */
    const dropdowns = [
      { btn: "langBtn", menu: "langMenu" },
      { btn: "notifBtn", menu: "notifMenu" },
      { btn: "profileBtn", menu: "profileMenu" },
    ];

    dropdowns.forEach(({ btn, menu }) => {
      const $btn = document.getElementById(btn);
      const $menu = document.getElementById(menu);
      if (!$btn || !$menu) return;

      $btn.addEventListener("click", (e) => {
        e.stopPropagation();
        // Close others
        dropdowns.forEach(d => {
          if (d.menu !== menu) document.getElementById(d.menu)?.classList.remove("show");
        });
        // Toggle current
        $menu.classList.toggle("show");
      });

      // Close on outside click
      document.addEventListener("click", (e) => {
        if (!$menu.contains(e.target) && !$btn.contains(e.target)) {
          $menu.classList.remove("show");
        }
      });
    });

    /* ---------- Language toggle (AR/EN) with dir + flag + persistence) ---------- */
    const htmlEl = document.documentElement;
    const langFlag = document.getElementById("langFlag");
    const langMenu = document.getElementById("langMenu");

    // Apply saved language on load
    const savedLang = localStorage.getItem("ui:lang");
    if (savedLang === "en") setLang("en"); else setLang("ar");

    // Handle menu clicks
    langMenu.querySelectorAll("[data-lang]").forEach(btn => {
      btn.addEventListener("click", () => {
        const v = btn.getAttribute("data-lang");
        setLang(v);
        langMenu.classList.remove("show");
      });
    });

    // Core: set language + direction + flag + persist
    function setLang(code) {
      if (code === "en") {
        htmlEl.setAttribute("lang", "en");
        htmlEl.setAttribute("dir", "ltr");
        langFlag.textContent = "🇬🇧";
        localStorage.setItem("ui:lang", "en");
        // NOTE: Here you can hook i18n strings switch if needed
      } else {
        htmlEl.setAttribute("lang", "ar");
        htmlEl.setAttribute("dir", "rtl");
        langFlag.textContent = "🇴🇲";
        localStorage.setItem("ui:lang", "ar");
      }
    }

document.addEventListener("DOMContentLoaded", () => {
  const menuBtn = document.getElementById("menuBtn");
  const sidebar = document.getElementById("sidebar");
  const overlay = document.getElementById("overlay");

  if (!menuBtn || !sidebar || !overlay) return;

  // عند الضغط على زر القائمة
  menuBtn.addEventListener("click", (e) => {
    e.stopPropagation(); // يمنع التداخل
    sidebar.classList.toggle("open");
    overlay.style.display = sidebar.classList.contains("open") ? "block" : "none";
  });

  // عند الضغط على الخلفية (يغلق القائمة)
  overlay.addEventListener("click", () => {
    sidebar.classList.remove("open");
    overlay.style.display = "none";
  });

  // لو ضغط المستخدم على أي مكان في الصفحة غير القائمة، تغلق تلقائيًا
  document.addEventListener("click", (e) => {
    if (
      sidebar.classList.contains("open") &&
      !sidebar.contains(e.target) &&
      e.target !== menuBtn
    ) {
      sidebar.classList.remove("open");
      overlay.style.display = "none";
    }
  });
});
