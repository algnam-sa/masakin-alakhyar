/* ==========================================================
   بيانات مشروع مساكن الأخيار
   عدّل هذا الملف فقط لتحديث معلومات التواصل وحالة الوحدات.
   ========================================================== */

window.SITE_CONFIG = {
  // رقم الجوال المعروض (يُرجى استبداله بالرقم الفعلي لإدارة المبيعات)
  phoneDisplay: "05XXXXXXXX",
  // رقم الجوال بالصيغة الدولية بدون + (مثال: 9665XXXXXXXX) — يستخدم للاتصال وواتساب
  phoneIntl: "",
  email: "",
  mapQuery: "حي النخيل، بريدة، القصيم",
};

/* نماذج الشقق — حسب لوحات النماذج المرفقة */
window.UNIT_MODELS = {
  A: {
    name: "نموذج A",
    area: 93.1,
    image: "assets/img/model-a.webp",
    rooms: ["صالة", "تراس", "غرفة نوم رئيسية", "غرفة نوم", "مطبخ", "2 دورات مياه", "غرفة غسيل"],
    units: [1, 4, 5, 6, 7, 8, 9, 12, 13, 14, 15, 16, 17, 20, 21, 22, 23, 24, 25, 28, 29, 30, 31, 32],
  },
  B: {
    name: "نموذج B",
    area: 88.8,
    image: "assets/img/model-b.webp",
    rooms: ["صالة", "تراس", "غرفة نوم رئيسية", "غرفة نوم", "مطبخ", "2 دورات مياه"],
    units: [2, 3, 10, 11, 18, 19, 26, 27, 34, 35],
  },
  C: {
    name: "نموذج C",
    area: 93.1,
    roof: 93.1,
    image: "assets/img/model-c.webp",
    rooms: ["صالة", "تراس", "غرفة نوم رئيسية", "غرفة نوم", "مطبخ", "2 دورات مياه", "غرفة غسيل", "سطح خاص"],
    units: [33, 36],
  },
};

/* بيان مكونات ومساحات الشقق — حسب جدول "بيان بمكونات ومساحات شقق العمارة"
   status: "available" متاحة | "reserved" محجوزة | "sold" مباعة            */
(function () {
  const floorNames = { 1: "الدور الأول", 2: "الدور الثاني", 3: "الدور الثالث", 4: "الدور الرابع", 5: "الملحق العلوي" };
  // نمط الدور المتكرر (8 شقق): [المساحة، غرفة غسيل، تراس]
  const typical = [
    [94.05, true, true], [92.15, true, true], [92.15, true, true], [94.05, true, true],
    [92.07, true, true], [85.92, false, true], [85.92, false, true], [92.07, true, true],
  ];
  const annex = [
    { area: 86.31, laundry: true, terrace: false, roof: 94.05 },
    { area: 85.92, laundry: false, terrace: false },
    { area: 85.92, laundry: false, terrace: false },
    { area: 85.92, laundry: true, terrace: false, roof: 94.05 },
  ];
  const modelOf = (n) => Object.keys(window.UNIT_MODELS).find((k) => window.UNIT_MODELS[k].units.includes(n));

  const units = [];
  for (let f = 1; f <= 4; f++) {
    typical.forEach(([area, laundry, terrace], i) => {
      const no = (f - 1) * 8 + i + 1;
      units.push({ no, floor: f, floorName: floorNames[f], area, laundry, terrace, roof: null, model: modelOf(no), status: "available" });
    });
  }
  annex.forEach((u, i) => {
    const no = 33 + i;
    units.push({ no, floor: 5, floorName: floorNames[5], area: u.area, laundry: u.laundry, terrace: u.terrace, roof: u.roof || null, model: modelOf(no), status: "available" });
  });

  // لتغيير حالة وحدة: أضف رقمها هنا، مثال: { 5: "sold", 12: "reserved" }
  const statusOverrides = {};
  units.forEach((u) => { if (statusOverrides[u.no]) u.status = statusOverrides[u.no]; });

  window.UNITS = units;
})();
