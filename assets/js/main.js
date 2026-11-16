(function () {
  "use strict";
  const $ = (s, el = document) => el.querySelector(s);
  const $$ = (s, el = document) => [...el.querySelectorAll(s)];
  const CFG = window.SITE_CONFIG;
  const MODELS = window.UNIT_MODELS;
  const UNITS = window.UNITS;
  const fmt = (n, d = 2) => Number(n).toLocaleString("en-US", { minimumFractionDigits: d, maximumFractionDigits: d });
  const STATUS = { available: "متاحة", reserved: "محجوزة", sold: "مباعة" };

  /* ---------- Header ---------- */
  const header = $(".site-header");
  const onScroll = () => header.classList.toggle("is-solid", window.scrollY > 40);
  onScroll();
  window.addEventListener("scroll", onScroll, { passive: true });

  const toggle = $("#navToggle");
  toggle.addEventListener("click", () => {
    const open = header.classList.toggle("nav-open");
    toggle.setAttribute("aria-expanded", open);
  });
  $$("#mainNav a").forEach((a) => a.addEventListener("click", () => {
    header.classList.remove("nav-open");
    toggle.setAttribute("aria-expanded", "false");
  }));

  // Active link highlight
  const navLinks = $$("#mainNav a:not(.btn)");
  const spy = new IntersectionObserver((entries) => {
    entries.forEach((e) => {
      if (e.isIntersecting) navLinks.forEach((a) => a.classList.toggle("is-current", a.getAttribute("href") === "#" + e.target.id));
    });
  }, { rootMargin: "-45% 0px -50% 0px" });
  navLinks.forEach((a) => { const s = $(a.getAttribute("href")); if (s) spy.observe(s); });

  /* ---------- Reveal ---------- */
  const revealer = new IntersectionObserver((entries) => {
    entries.forEach((e) => { if (e.isIntersecting) { e.target.classList.add("is-visible"); revealer.unobserve(e.target); } });
  }, { threshold: 0.12 });
  $$(".reveal").forEach((el) => revealer.observe(el));

  /* ---------- Counters ---------- */
  const counter = new IntersectionObserver((entries) => {
    entries.forEach((e) => {
      if (!e.isIntersecting) return;
      const el = e.target, target = parseFloat(el.dataset.count), dec = +(el.dataset.decimals || 0);
      const t0 = performance.now(), dur = 1400;
      const tick = (t) => {
        const p = Math.min(1, (t - t0) / dur), v = target * (1 - Math.pow(1 - p, 3));
        el.textContent = fmt(v, dec);
        if (p < 1) requestAnimationFrame(tick);
      };
      requestAnimationFrame(tick);
      counter.unobserve(el);
    });
  }, { threshold: 0.6 });
  $$("[data-count]").forEach((el) => counter.observe(el));

  /* ---------- Contact helpers ---------- */
  const waLink = (text) => CFG.phoneIntl ? `https://wa.me/${CFG.phoneIntl}?text=${encodeURIComponent(text)}` : "#contact";
  const phoneLink = $("#phoneLink");
  phoneLink.textContent = CFG.phoneDisplay;
  if (CFG.phoneIntl) phoneLink.href = "tel:+" + CFG.phoneIntl;
  if (CFG.phoneIntl) {
    const wa = $("#waFloat");
    wa.href = waLink("السلام عليكم، أرغب بالاستفسار عن مشروع مساكن الأخيار");
    wa.hidden = false;
  }

  /* ---------- Map ---------- */
  const q = `${CFG.mapLat},${CFG.mapLng}`;
  const mapUrl = `https://www.google.com/maps/search/?api=1&query=${q}`;
  const mapFrame = $("#mapFrame");
  if (mapFrame) mapFrame.src = `https://maps.google.com/maps?q=${q}&z=17&output=embed`;
  $("#mapLink").href = mapUrl;

  /* ---------- Models ---------- */
  const modelTabs = $("#modelTabs"), modelPanel = $("#modelPanel");
  Object.entries(MODELS).forEach(([key, m], i) => {
    const b = document.createElement("button");
    b.className = "tab" + (i === 0 ? " is-active" : "");
    b.setAttribute("role", "tab");
    b.setAttribute("aria-selected", i === 0);
    b.dataset.model = key;
    b.textContent = m.name + (m.roof ? " · مع سطح" : "");
    modelTabs.appendChild(b);
  });
  const renderModel = (key) => {
    const m = MODELS[key];
    modelPanel.classList.remove("is-switching"); void modelPanel.offsetWidth; modelPanel.classList.add("is-switching");
    modelPanel.innerHTML = `
      <div class="model-img"><img src="${m.image}" alt="مخطط ثلاثي الأبعاد ل${m.name}" data-lightbox="${m.image}"></div>
      <div class="model-info">
        <span class="model-badge">${key}</span>
        <h3>${m.name}</h3>
        <div class="model-area">
          <div><b>${fmt(m.area)}</b><span>م² مساحة الشقة</span></div>
          ${m.roof ? `<div><b>${fmt(m.roof)}</b><span>م² مساحة السطح</span></div>` : ""}
          <div><b>${m.units.length}</b><span>شقة من هذا النموذج</span></div>
        </div>
        <ul class="room-list">${m.rooms.map((r) => `<li>${r}</li>`).join("")}</ul>
        <p class="model-units">الشقق: <b>${m.units.join("، ")}</b></p>
        <a href="#units" class="btn btn-primary" data-goto-model="${key}">عرض شقق ${m.name}</a>
      </div>`;
  };
  modelTabs.addEventListener("click", (e) => {
    const b = e.target.closest(".tab"); if (!b) return;
    $$(".tab", modelTabs).forEach((t) => { t.classList.toggle("is-active", t === b); t.setAttribute("aria-selected", t === b); });
    renderModel(b.dataset.model);
  });
  renderModel(Object.keys(MODELS)[0]);

  /* ---------- Units grid ---------- */
  const building = $("#building");
  const floors = [...new Set(UNITS.map((u) => u.floor))].sort((a, b) => b - a);
  floors.forEach((f) => {
    const list = UNITS.filter((u) => u.floor === f);
    const avail = list.filter((u) => u.status === "available").length;
    const row = document.createElement("div");
    row.className = "floor-row";
    row.dataset.floor = f;
    row.innerHTML = `
      <div class="floor-label"><b>${list[0].floorName}</b><span>${avail} من ${list.length} متاحة</span></div>
      <div class="floor-units">${list.map((u) => `
        <button class="unit" data-no="${u.no}" data-model="${u.model}" data-status="${u.status}" aria-label="شقة ${u.no} - ${STATUS[u.status]}">
          <b>${u.no}</b><small>${fmt(u.area)} م²</small><span class="unit-model">${u.model}</span>
        </button>`).join("")}
      </div>`;
    building.appendChild(row);
  });

  let fFloor = "all", fModel = "all";
  const applyFilters = () => {
    $$(".floor-row", building).forEach((r) => r.classList.toggle("is-hidden", fFloor !== "all" && r.dataset.floor !== fFloor));
    $$(".unit", building).forEach((u) => u.classList.toggle("is-dim", fModel !== "all" && u.dataset.model !== fModel));
  };
  const bindChips = (id, set) => $(id).addEventListener("click", (e) => {
    const c = e.target.closest(".chip"); if (!c) return;
    $$(".chip", $(id)).forEach((x) => x.classList.toggle("is-active", x === c));
    set(c); applyFilters();
  });
  bindChips("#floorFilter", (c) => (fFloor = c.dataset.floor));
  bindChips("#modelFilter", (c) => (fModel = c.dataset.model));

  document.addEventListener("click", (e) => {
    const g = e.target.closest("[data-goto-model]"); if (!g) return;
    const chip = $(`#modelFilter .chip[data-model="${g.dataset.gotoModel}"]`);
    chip && chip.click();
  });

  /* ---------- Unit modal ---------- */
  const modal = $("#unitModal"), body = $("#unitBody");
  let lastFocus = null;
  const openModal = (el) => { lastFocus = document.activeElement; el.hidden = false; document.body.classList.add("no-scroll"); $(".modal-close", el).focus(); };
  const closeModal = (el) => { el.hidden = true; document.body.classList.remove("no-scroll"); lastFocus && lastFocus.focus(); };

  building.addEventListener("click", (e) => {
    const b = e.target.closest(".unit"); if (!b) return;
    const u = UNITS.find((x) => x.no === +b.dataset.no), m = MODELS[u.model];
    const rooms = ["صالة", "غرفة نوم رئيسية", "غرفة نوم", "مطبخ", "2 دورات مياه"];
    if (u.laundry) rooms.push("غرفة غسيل");
    if (u.terrace) rooms.push("تراس");
    if (u.roof) rooms.push("سطح خاص");
    const msg = `السلام عليكم، أرغب بالاستفسار عن الشقة رقم ${u.no} (${m.name}) - ${u.floorName} في مشروع مساكن الأخيار`;
    body.innerHTML = `
      <div class="unit-modal-img"><img src="${m.image}" alt="${m.name}"></div>
      <div class="unit-modal-body">
        <span class="status-pill"><i class="dot ${u.status}"></i>${STATUS[u.status]}</span>
        <h3 id="unitTitle">شقة رقم ${u.no}</h3>
        <p>${m.name} · ${u.floorName}</p>
        <div class="spec-grid">
          <div><span>مساحة الشقة</span><b>${fmt(u.area)} م²</b></div>
          ${u.roof ? `<div><span>السطح الخاص</span><b>${fmt(u.roof)} م²</b></div>` : ""}
          <div><span>غرف النوم</span><b>2</b></div>
          <div><span>دورات المياه</span><b>2</b></div>
        </div>
        <p><b>المكونات:</b> ${rooms.join(" · ")}</p>
        <div class="unit-modal-actions">
          ${u.status !== "sold" ? `<a class="btn btn-primary" ${CFG.phoneIntl ? `href="${waLink(msg)}" target="_blank" rel="noopener"` : `href="#contact" data-prefill="${u.no}" data-close`}>استفسر عن الشقة</a>` : ""}
          <button class="btn btn-outline" data-close>إغلاق</button>
        </div>
      </div>`;
    openModal(modal);
  });
  modal.addEventListener("click", (e) => {
    const p = e.target.closest("[data-prefill]");
    if (p) {
      const u = UNITS.find((x) => x.no === +p.dataset.prefill);
      $("#fUnit").value = u.no; $("#fModel").value = u.model;
    }
    if (e.target.closest("[data-close]")) closeModal(modal);
  });

  /* ---------- Lightbox ---------- */
  const lb = $("#lightbox"), lbImg = $("img", lb);
  document.addEventListener("click", (e) => {
    const img = e.target.closest("[data-lightbox]"); if (!img) return;
    lbImg.src = img.dataset.lightbox; lbImg.alt = img.alt;
    openModal(lb);
  });
  lb.addEventListener("click", (e) => { if (e.target !== lbImg) closeModal(lb); });
  document.addEventListener("keydown", (e) => {
    if (e.key !== "Escape") return;
    if (!modal.hidden) closeModal(modal);
    if (!lb.hidden) closeModal(lb);
  });

  /* ---------- Plans ---------- */
  const PLANS = {
    typical: ["assets/img/plan-typical.webp", "مسقط أفقي للدور المتكرر – 8 شقق بمساحة بناء 825.37 م²"],
    ground: ["assets/img/plan-ground.webp", "مسقط أفقي للدور الأرضي – مواقف سيارات بمساحة 711.88 م²"],
    annex: ["assets/img/plan-annex.webp", "مسقط أفقي للملحق العلوي – 4 شقق بمساحة بناء 367.60 م²"],
  };
  const planImg = $("#planImg");
  $("#planTabs").addEventListener("click", (e) => {
    const b = e.target.closest(".tab"); if (!b) return;
    $$("#planTabs .tab").forEach((t) => { t.classList.toggle("is-active", t === b); t.setAttribute("aria-selected", t === b); });
    const [src, cap] = PLANS[b.dataset.plan];
    planImg.style.opacity = 0;
    setTimeout(() => { planImg.src = src; planImg.dataset.lightbox = src; planImg.alt = cap; $("#planCaption").textContent = cap; planImg.style.opacity = 1; }, 200);
  });

  /* ---------- Lead form ---------- */
  const form = $("#leadForm"), status = $("#formStatus");
  form.addEventListener("submit", (e) => {
    e.preventDefault();
    let ok = true;
    $$("input[required]", form).forEach((i) => {
      const valid = i.value.trim() && (!i.pattern || new RegExp(i.pattern).test(i.value.replace(/\s/g, "")));
      i.closest(".field").classList.toggle("has-error", !valid);
      if (!valid) ok = false;
    });
    if (!ok) { status.className = "form-status err"; status.textContent = "يرجى إدخال الاسم ورقم جوال صحيح (05xxxxxxxx)."; return; }

    const d = Object.fromEntries(new FormData(form));
    const lines = [
      "طلب اهتمام - مشروع مساكن الأخيار",
      `الاسم: ${d.name}`, `الجوال: ${d.phone}`,
      d.model && `النموذج: ${d.model}`, d.unit && `رقم الشقة: ${d.unit}`,
      d.message && `ملاحظات: ${d.message}`,
    ].filter(Boolean).join("\n");

    if (CFG.phoneIntl) {
      window.open(waLink(lines), "_blank", "noopener");
    } else if (CFG.email) {
      location.href = `mailto:${CFG.email}?subject=${encodeURIComponent("طلب اهتمام - مساكن الأخيار")}&body=${encodeURIComponent(lines)}`;
    } else {
      status.className = "form-status err";
      status.textContent = "لم يتم ربط وسيلة استقبال الطلبات بعد. يرجى التواصل هاتفيًا.";
      console.warn("SITE_CONFIG.phoneIntl / email غير مُعدّة في assets/js/data.js");
      return;
    }
    status.className = "form-status ok";
    status.textContent = "شكرًا لك! تم استلام طلبك وسيتواصل معك فريق المبيعات قريبًا.";
    form.reset();
  });

  /* ---------- Hero lead: two full lines under the title ---------- */
  const lead = $(".hero-lead");
  const fitLead = () => {
    if (!lead) return;
    let lo = 11, hi = 28;
    for (let i = 0; i < 14; i++) {
      const mid = (lo + hi) / 2;
      lead.style.fontSize = mid + "px";
      const lh = parseFloat(getComputedStyle(lead).lineHeight);
      if (lead.scrollHeight <= lh * 2 + 1) lo = mid; else hi = mid;
    }
    lead.style.fontSize = (Math.floor(lo * 10) / 10 - 0.2) + "px"; // round down with a small safety margin
  };
  fitLead();
  // Re-fit whenever the title's width changes (web font load, resize)
  const heroTitle = $(".hero-headline h1");
  if (heroTitle && "ResizeObserver" in window) new ResizeObserver(() => requestAnimationFrame(fitLead)).observe(heroTitle);
  if (document.fonts && document.fonts.ready) document.fonts.ready.then(fitLead);
  window.addEventListener("load", fitLead);

  /* ---------- Feature cards: light runs a zigzag path along each row (both rows at once) ---------- */
  const featGrid = $(".features-grid");
  const featCards = $$(".feature", featGrid);
  if (featGrid && featCards.length && !window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
    const NS = "http://www.w3.org/2000/svg";
    const svg = document.createElementNS(NS, "svg");
    svg.setAttribute("class", "feature-trail");
    svg.setAttribute("aria-hidden", "true");
    svg.innerHTML = '<defs><linearGradient id="trailGradient" gradientUnits="userSpaceOnUse"><stop offset="0" stop-color="#c9a063"/><stop offset="1" stop-color="#f39925"/></linearGradient></defs>';
    featGrid.appendChild(svg);
    const SPEED = 520;   // px per second
    const DASH = 240;    // length of the moving light
    let anims = [], visible = false;

    // Rounded polyline through points (corner radius r)
    const roundedPath = (pts, r) => {
      let d = `M${pts[0][0]},${pts[0][1]}`;
      for (let i = 1; i < pts.length - 1; i++) {
        const [px, py] = pts[i - 1], [cx, cy] = pts[i], [nx, ny] = pts[i + 1];
        const l1 = Math.hypot(cx - px, cy - py), l2 = Math.hypot(nx - cx, ny - cy);
        const k = Math.min(r, l1 / 2, l2 / 2);
        const ax = cx - ((cx - px) / l1) * k, ay = cy - ((cy - py) / l1) * k;
        const bx = cx + ((nx - cx) / l2) * k, by = cy + ((ny - cy) / l2) * k;
        d += ` L${ax},${ay} Q${cx},${cy} ${bx},${by}`;
      }
      const last = pts[pts.length - 1];
      return d + ` L${last[0]},${last[1]}`;
    };

    const build = () => {
      anims.forEach((a) => a.cancel()); anims = [];
      svg.querySelectorAll("g").forEach((g) => g.remove());
      const g0 = featGrid.getBoundingClientRect();
      const boxes = featCards.map((el) => {
        const r = el.getBoundingClientRect();
        return { l: r.left - g0.left, r: r.right - g0.left, t: r.top - g0.top, b: r.bottom - g0.top };
      });
      // group cards into rows (same top), keep DOM order = reading order (right to left)
      const rows = [];
      boxes.forEach((bx) => {
        const row = rows.find((rw) => Math.abs(rw[0].t - bx.t) < 4);
        row ? row.push(bx) : rows.push([bx]);
      });
      if (rows[0].length < 2) return; // single column: no path between cards
      rows.forEach((row) => {
        const top = Math.min(...row.map((c) => c.t)), bot = Math.max(...row.map((c) => c.b));
        // start on the outer (right) edge of the first card's top, then alternate: top of card 1, bottom of card 2, top of card 3...
        const pts = [[row[0].r, top]];
        let y = top;
        for (let i = 0; i < row.length - 1; i++) {
          const gx = (row[i].l + row[i + 1].r) / 2; // middle of the gap between cards
          pts.push([gx, y]);
          y = y === top ? bot : top;
          pts.push([gx, y]);
        }
        pts.push([row[row.length - 1].l, y]);
        const d = roundedPath(pts, 14);
        const g = document.createElementNS(NS, "g");
        g.innerHTML = `<path class="trail-track" d="${d}"/><path class="trail-glow" d="${d}"/><path class="trail-line" d="${d}"/>`;
        svg.appendChild(g);
        const len = g.querySelector(".trail-line").getTotalLength();
        g.querySelectorAll(".trail-glow, .trail-line").forEach((path) => {
          path.style.strokeDasharray = `${DASH} ${len + DASH}`;
          const a = path.animate(
            [{ strokeDashoffset: DASH }, { strokeDashoffset: -len }],
            { duration: ((len + DASH) / SPEED) * 1000, iterations: Infinity, easing: "linear" }
          );
          if (!visible) a.pause();
          anims.push(a);
        });
      });
      // keep both rows in step: give every animation the longest duration
      const maxDur = Math.max(...anims.map((a) => a.effect.getTiming().duration));
      anims.forEach((a) => { a.effect.updateTiming({ duration: maxDur }); a.currentTime = 0; });
    };
    build();
    if (document.fonts && document.fonts.ready) document.fonts.ready.then(build);
    let rt; new ResizeObserver(() => { clearTimeout(rt); rt = setTimeout(build, 150); }).observe(featGrid);
    new IntersectionObserver((entries) => entries.forEach((e) => {
      visible = e.isIntersecting;
      anims.forEach((a) => (visible ? a.play() : a.pause()));
    }), { threshold: 0.15 }).observe(featGrid);
  }

  /* ---------- About video: pause for reduced motion ---------- */
  if (window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
    $$(".about-video, .hero-bg").forEach((v) => { if (v.pause) { v.removeAttribute("autoplay"); v.pause(); } });
  }

  $("#year").textContent = new Date().getFullYear();
})();
