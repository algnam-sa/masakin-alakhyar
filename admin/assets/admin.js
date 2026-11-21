(function () {
  "use strict";
  const $ = (s, el = document) => el.querySelector(s);
  const $$ = (s, el = document) => [...el.querySelectorAll(s)];
  const csrf = ($('meta[name="csrf-token"]') || {}).content || "";

  /* ---------- Sidebar (mobile) ---------- */
  document.addEventListener("click", (e) => {
    if (e.target.closest("[data-sb-toggle]")) document.body.classList.toggle("sb-open");
    else if (e.target.closest("[data-sb-close]")) document.body.classList.remove("sb-open");
  });

  /* ---------- Confirmations ---------- */
  document.addEventListener("submit", (e) => {
    const f = e.target.closest("[data-confirm]");
    if (f && !window.confirm(f.dataset.confirm)) e.preventDefault();
  });

  /* ---------- Auto-submit selects (lead status in the list) ---------- */
  document.addEventListener("change", (e) => {
    const s = e.target.closest("[data-autosubmit]");
    if (s) s.form.submit();
  });

  /* ---------- Auto-growing textareas ---------- */
  const grow = (t) => { t.style.height = "auto"; t.style.height = Math.min(t.scrollHeight + 2, 600) + "px"; };
  document.addEventListener("input", (e) => { if (e.target.matches("textarea[data-autosize]")) grow(e.target); });
  $$("textarea[data-autosize]").forEach(grow);

  /* ---------- Unsaved changes ---------- */
  const tracked = $("#unitsForm") || $("#contentForm");
  let dirty = false;
  const markDirty = (row) => {
    if (!tracked) return;
    dirty = true;
    const bar = $(".sticky-save", tracked);
    if (bar) { bar.classList.add("dirty"); $("#dirtyNote").textContent = "لديك تعديلات غير محفوظة"; }
    if (row) row.classList.add("is-dirty");
  };
  if (tracked) {
    tracked.addEventListener("input", (e) => markDirty(e.target.closest("tr")));
    tracked.addEventListener("change", (e) => markDirty(e.target.closest("tr")));
    tracked.addEventListener("submit", () => { dirty = false; });
    window.addEventListener("beforeunload", (e) => { if (dirty) { e.preventDefault(); e.returnValue = ""; } });
  }

  /* ---------- Units page ---------- */
  const unitsForm = $("#unitsForm");
  if (unitsForm) {
    let statusFilter = (new URLSearchParams(location.search)).get("status") || "";
    const search = $("#unitSearch");
    const apply = () => {
      const q = (search.value || "").trim();
      $$("tbody tr", unitsForm).forEach((tr) => {
        const okStatus = !statusFilter || tr.querySelector(".status-select").value === statusFilter;
        const okSearch = !q || tr.dataset.search.includes(q);
        tr.classList.toggle("is-hidden", !(okStatus && okSearch));
      });
    };
    $$(".seg-filter [data-filter]").forEach((b) => b.addEventListener("click", () => {
      statusFilter = b.dataset.filter;
      $$(".seg-filter [data-filter]").forEach((x) => x.classList.toggle("on", x === b));
      apply();
    }));
    search.addEventListener("input", apply);
    apply();
    unitsForm.addEventListener("change", (e) => {
      if (e.target.matches(".status-select")) e.target.className = "status-select st-" + e.target.value;
    });
    unitsForm.addEventListener("click", (e) => {
      const b = e.target.closest("[data-delete-unit]");
      if (!b) return;
      const no = b.dataset.deleteUnit;
      if (!window.confirm(`حذف الشقة رقم ${no} نهائيًا من الموقع؟`)) return;
      const f = $("#deleteUnitForm");
      f.elements.no.value = no;
      dirty = false;
      f.submit();
    });
  }

  /* ---------- Repeatable lists ---------- */
  const renumber = (list) => $$(":scope > .list-items > .list-item", list).forEach((it, i) => {
    const n = $(".item-no", it); if (n) n.textContent = i + 1;
  });
  document.addEventListener("click", (e) => {
    const add = e.target.closest("[data-add]");
    if (add) {
      const list = add.closest("[data-list]");
      const tpl = $(":scope > template", list);
      const html = tpl.innerHTML.replaceAll("__i__", "n" + Date.now().toString(36) + Math.random().toString(36).slice(2, 6));
      const box = $(":scope > .list-items", list);
      box.insertAdjacentHTML("beforeend", html);
      const item = box.lastElementChild;
      item.classList.add("flash");
      initIcons(item);
      renumber(list);
      markDirty();
      const first = $("input, textarea, select", item);
      if (first) first.focus();
      return;
    }
    const tools = e.target.closest("[data-remove], [data-up], [data-down]");
    if (tools) {
      const item = tools.closest(".list-item"), list = item.closest("[data-list]");
      if (tools.matches("[data-remove]")) {
        if (!window.confirm("حذف هذا العنصر؟")) return;
        item.remove();
      } else if (tools.matches("[data-up]") && item.previousElementSibling) {
        item.parentNode.insertBefore(item, item.previousElementSibling);
      } else if (tools.matches("[data-down]") && item.nextElementSibling) {
        item.parentNode.insertBefore(item.nextElementSibling, item);
      }
      item.classList.remove("flash"); void item.offsetWidth; item.classList.add("flash");
      renumber(list);
      markDirty();
    }
  });

  /* ---------- Icon picker preview (features) ---------- */
  const initIcons = (root) => {
    if (!window.ICON_SVGS) return;
    $$("select[data-icon-select]", root).forEach((sel) => {
      let prev = sel.parentNode.querySelector(".icon-preview");
      if (!prev) { prev = document.createElement("span"); prev.className = "icon-preview"; sel.after(prev); }
      const draw = () => { prev.innerHTML = `<svg viewBox="0 0 24 24">${window.ICON_SVGS[sel.value] || ""}</svg>`; };
      sel.addEventListener("change", draw);
      draw();
    });
  };
  initIcons(document);

  /* ---------- Media fields: upload, pick, clear ---------- */
  const setPreview = (field, path) => {
    const input = $(".media-path", field);
    input.value = path;
    const prev = $(".media-preview", field);
    if (prev) {
      const isVideo = /\.(mp4|webm)$/i.test(path);
      const isPdf = /\.pdf$/i.test(path);
      const src = /^https?:/i.test(path) ? path : "../" + path;
      prev.innerHTML = !path ? '<span class="media-empty">لا يوجد ملف</span>'
        : isPdf ? `<a href="${src}" target="_blank" rel="noopener"><span class="pdf-thumb"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 2h9l5 5v15H6z"/><path d="M15 2v5h5"/></svg><b>PDF</b></span></a>`
        : isVideo ? `<video src="${src}" muted preload="metadata"></video>` : `<img src="${src}" alt="">`;
    }
    markDirty();
  };

  document.addEventListener("click", (e) => {
    const field = e.target.closest(".media-field");
    if (!field) return;
    if (e.target.closest("[data-upload]")) $('input[type="file"]', field).click();
    if (e.target.closest("[data-clear]")) setPreview(field, "");
    if (e.target.closest("[data-pick]")) openPicker(field);
  });

  document.addEventListener("change", (e) => {
    if (!e.target.matches('.media-field input[type="file"]')) return;
    const field = e.target.closest(".media-field"), file = e.target.files[0];
    if (!file) return;
    const status = $(".upload-status", field);
    const fd = new FormData();
    fd.append("file", file);
    fd.append("_csrf", csrf);
    status.className = "upload-status";
    status.textContent = `جارٍ رفع ${file.name}…`;
    const xhr = new XMLHttpRequest();
    xhr.open("POST", "upload.php");
    xhr.setRequestHeader("X-CSRF-Token", csrf);
    xhr.upload.onprogress = (ev) => { if (ev.lengthComputable) status.textContent = `جارٍ الرفع… ${Math.round(ev.loaded / ev.total * 100)}%`; };
    xhr.onload = () => {
      let r = {};
      try { r = JSON.parse(xhr.responseText); } catch (_) {}
      if (xhr.status === 200 && r.ok) {
        if (field.hasAttribute("data-library-upload")) { location.reload(); return; }
        setPreview(field, r.path);
        status.className = "upload-status ok";
        status.textContent = "تم الرفع. اضغط \"حفظ التعديلات\" لاعتماده في الموقع.";
      } else {
        status.className = "upload-status err";
        status.textContent = r.error || "تعذّر رفع الملف. قد يكون حجمه أكبر من حد الاستضافة.";
      }
      e.target.value = "";
    };
    xhr.onerror = () => { status.className = "upload-status err"; status.textContent = "انقطع الاتصال أثناء الرفع. حاول مرة أخرى."; };
    xhr.send(fd);
  });

  /* Library picker modal */
  const modal = $("#mediaModal");
  let pickTarget = null;
  const openPicker = (field) => {
    if (!modal) return;
    pickTarget = field;
    modal.hidden = false;
    const grid = $("#mediaGrid");
    grid.innerHTML = '<p class="muted">جارٍ التحميل…</p>';
    const type = ["video", "image", "pdf"].includes(field.dataset.media) ? field.dataset.media : "";
    fetch("media.php?json=1&type=" + type, { headers: { Accept: "application/json" } })
      .then((r) => r.json())
      .then((d) => {
        if (!d.files || !d.files.length) { grid.innerHTML = '<p class="muted">لا توجد ملفات. ارفع ملفًا أولًا.</p>'; return; }
        grid.innerHTML = "";
        d.files.forEach((f) => {
          const b = document.createElement("button");
          b.type = "button";
          b.dataset.path = f.path;
          const src = "../" + f.path;
          b.innerHTML = `<span class="mg-thumb">${f.type === "pdf" ? '<span class="pdf-thumb"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 2h9l5 5v15H6z"/><path d="M15 2v5h5"/></svg><b>PDF</b></span>' : f.type === "video" ? `<video src="${src}" muted preload="metadata"></video>` : `<img src="${src}" alt="" loading="lazy">`}</span><span class="mg-name"></span>`;
          b.querySelector(".mg-name").textContent = f.path.split("/").pop();
          grid.appendChild(b);
        });
        const firstBtn = $("button", grid); if (firstBtn) firstBtn.focus();
      })
      .catch(() => { grid.innerHTML = '<p class="muted">تعذّر تحميل المكتبة.</p>'; });
  };
  if (modal) {
    modal.addEventListener("click", (e) => {
      const b = e.target.closest("#mediaGrid button");
      if (b && pickTarget) { setPreview(pickTarget, b.dataset.path); modal.hidden = true; return; }
      if (e.target.closest("[data-close]")) modal.hidden = true;
    });
    document.addEventListener("keydown", (e) => { if (e.key === "Escape") modal.hidden = true; });
  }

  /* ---------- Permissions editor ---------- */
  document.addEventListener("change", (e) => {
    const t = e.target.closest("[data-admin-toggle]");
    if (!t) return;
    const groups = t.closest("form").querySelector("[data-perm-groups]");
    if (groups) groups.classList.toggle("is-off", t.checked);
  });
  document.addEventListener("click", (e) => {
    const b = e.target.closest("[data-check-all]");
    if (!b) return;
    const boxes = [...b.closest(".perm-group").querySelectorAll('input[type="checkbox"]')];
    const allOn = boxes.every((x) => x.checked);
    boxes.forEach((x) => { x.checked = !allOn; });
    b.textContent = allOn ? "تحديد الكل" : "إلغاء الكل";
  });

  /* ---------- Copy media path ---------- */
  document.addEventListener("click", (e) => {
    const b = e.target.closest("[data-copy]");
    if (!b) return;
    const done = () => { const t = b.textContent; b.textContent = "تم النسخ"; setTimeout(() => (b.textContent = t), 1400); };
    if (navigator.clipboard) navigator.clipboard.writeText(b.dataset.copy).then(done).catch(() => window.prompt("انسخ المسار:", b.dataset.copy));
    else window.prompt("انسخ المسار:", b.dataset.copy);
  });
})();
