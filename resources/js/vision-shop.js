import { gsap } from "gsap";

/* VISION shop: approved store-fa interactions, backed by Laravel records. */
(() => {
  const S = window.SHOP || {};
  const P = Array.isArray(S.products) ? S.products : [];
  const C = ["همه دسته‌ها", ...(S.cats || [])];
  const OF = S.looks || {};
  const $ = (selector, parent = document) => parent.querySelector(selector);
  const fa = n => Number(n || 0).toLocaleString("fa-IR");
  const wait = ms => new Promise(resolve => window.setTimeout(resolve, ms));
  const RM = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  const CSRF = $('meta[name="csrf-token"]')?.content || "";
  const state = { visibleCount: 12, cat: C[0], q: S.initialQuery || "", sort: S.initialSort || "new", saleOnly: !!S.initialSaleOnly, max: Number(S.max || 4000000), fav: new Set((S.favs || []).map(Number)), worn: new Set(), cart: 0 };
  const W = new Set();
  const COMPARE_KEY = "vision.fit.compare.v1";
  const comparison = (() => {
    try {
      const saved = JSON.parse(window.localStorage.getItem(COMPARE_KEY) || "{}");
      const clean = ids => Array.isArray(ids)
        ? ids.map(Number).filter(id => P.some(product => Number(product.id) === id && product.slot))
        : [];
      return { A: clean(saved.A), B: clean(saved.B) };
    } catch (_) {
      return { A: [], B: [] };
    }
  })();
  let busy = false;
  let fittingTrigger = null;
  const pr = $("#pr"), grid = $("#grid"), stage = $("#stage"), fit = $("#fit");

  const escapeHtml = value => String(value ?? "").replace(/[&<>"']/g, char => ({
    "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;"
  })[char]);
  const safeColorCode = value => /^#(?:[0-9a-f]{3}|[0-9a-f]{6})$/i.test(String(value || ""))
    ? String(value)
    : "#8b8490";

  async function post(url, body = {}) {
    const response = await fetch(url, {
      method: "POST",
      credentials: "same-origin",
      headers: {
        "Content-Type": "application/json",
        "Accept": "application/json",
        "X-CSRF-TOKEN": CSRF,
        "X-Requested-With": "XMLHttpRequest"
      },
      body: JSON.stringify(body)
    });
    const data = await response.json().catch(() => ({}));
    if (!response.ok) {
      const message = data.message || Object.values(data.errors || {}).flat()[0] || "درخواست انجام نشد.";
      throw new Error(message);
    }
    return data;
  }

  const formatMoney = amount => fa(amount) + " <small>" + escapeHtml(S.currency || "تومان") + "</small>";

  function list() {
    let products = P.filter(product =>
      (state.cat === C[0] || product.cat === state.cat)
      && String(product.name || "").toLocaleLowerCase("fa").includes(state.q.toLocaleLowerCase("fa"))
      && Number(product.price || 0) <= state.max
      && (!state.saleOnly || (Number(product.old || 0) > Number(product.price || 0)))
    );
    const sorters = {
      lo: (a, b) => Number(a.price) - Number(b.price),
      hi: (a, b) => Number(b.price) - Number(a.price),
      rt: (a, b) => Number(b.rate || 0) - Number(a.rate || 0)
    };
    if (sorters[state.sort]) products = [...products].sort(sorters[state.sort]);
    return products;
  }

  function render() {
    const products = list();
    $("#n").textContent = fa(products.length) + " محصول";
    $("#prv").textContent = "تا " + fa(state.max) + " " + (S.currency || "تومان");
    grid.innerHTML = products.length ? products.slice(0, state.visibleCount).map(product => {
      const canFit = !!(Number(product.stock || 0) > 0 && product.slot && product.o && OF[String(product.o)]?.s?.[product.slot === "top" ? "T" : "B"]);
      const image = product.img
        ? '<img src="' + escapeHtml(product.img) + '" alt="' + escapeHtml(product.name) + '" loading="lazy" decoding="async">'
        : '<span class="shop-pic-empty" aria-label="تصویر محصول ثبت نشده">V.</span>';
      return '<article class="shop-product-card" data-product="' + Number(product.id) + '">' +
        '<div class="shop-product-side">' +
          '<button type="button" class="fav ' + (state.fav.has(Number(product.id)) ? "on" : "") + '" data-f="' + Number(product.id) + '" aria-pressed="' + state.fav.has(Number(product.id)) + '" aria-label="ذخیرهٔ ' + escapeHtml(product.name) + '" title="علاقه‌مندی">' +
            '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.5 8.8c0 5.1-8.5 10-8.5 10s-8.5-4.9-8.5-10A4.6 4.6 0 0 1 12 6.3a4.6 4.6 0 0 1 8.5 2.5Z"/></svg>' +
          '</button>' +
          '<button type="button" class="try ' + (state.worn.has(Number(product.id)) ? "on" : "") + '" data-t="' + Number(product.id) + '" aria-pressed="' + state.worn.has(Number(product.id)) + '" aria-label="امتحان ' + escapeHtml(product.name) + ' روی مانکن" title="' + (canFit ? "امتحان روی مانکن" : "تصویر مانکن برای این محصول ثبت نشده") + '"' + (canFit ? "" : " disabled") + '>' +
            '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 8.5V5.8a2.3 2.3 0 1 0-2.3-2.3"/><path d="m12 8.5-8.3 4.7a1.6 1.6 0 0 0 .8 3h15a1.6 1.6 0 0 0 .8-3L12 8.5Z"/></svg>' +
          '</button>' +
        '</div>' +
        (product.rate !== null && product.rate !== undefined ? '<span class="shop-rate">★ ' + fa(product.rate) + '</span>' : "") +
        '<a class="shop-pic" href="' + escapeHtml(product.url || "#") + '" aria-label="مشاهدهٔ ' + escapeHtml(product.name) + '">' + image + '</a>' +
        '<div class="shop-product-details">' +
          '<a class="shop-product-name" href="' + escapeHtml(product.url || "#") + '">' + escapeHtml(product.name) + '</a>' +
          (product.color ? '<span class="shop-product-colour" aria-label="رنگ ' + escapeHtml(product.color) + '"><i style="--shop-swatch:' + safeColorCode(product.colorCode) + '" aria-hidden="true"></i><span>' + escapeHtml(product.color) + '</span></span>' : '') +
        '</div>' +
        '<div class="shop-product-price">' + formatMoney(product.price) +
          (product.old && Number(product.old) > Number(product.price) ? '<s>' + fa(product.old) + '</s><em>حراج</em>' : "") +
        '</div>' +
        '<button type="button" class="shop-cart-add" data-c="' + Number(product.id) + '" aria-label="افزودن ' + escapeHtml(product.name) + ' به سبد خرید" ' + (Number(product.stock || 0) < 1 ? "disabled" : "") + '>' +
          '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 8h16l-1.2 12H5.2L4 8Z"/><path d="M8.5 8V6a3.5 3.5 0 0 1 7 0v2M12 12v4m-2-2h4"/></svg>' +
        '</button>' +
      '</article>';
    }).join("") : '<div class="shop-empty"><strong>چیزی برای این جست‌وجو پیدا نشد.</strong>عبارت یا فیلتر را عوض کن.</div>';

    const more = $("#shop-load-more");
    if (more) { more.hidden = products.length <= state.visibleCount; more.textContent = "نمایش محصولات بیشتر (" + fa(Math.min(12, products.length - state.visibleCount)) + ")"; }
    $("#cats").innerHTML = C.map(category =>
      '<button type="button" class="shop-category ' + (category === state.cat ? "on" : "") + '" data-cat="' + escapeHtml(category) + '" aria-pressed="' + (category === state.cat) + '">' + escapeHtml(category) + '</button>'
    ).join("");
  }

  // Preload one canonical mannequin canvas and the four scene states per look.
  function makeScene(url, active) {
    if (!url) return null;
    const image = new Image();
    image.src = url;
    image.className = "mm" + (active ? " on" : "");
    image.alt = "";
    image.setAttribute("aria-hidden", "true");
    stage.insertBefore(image, $("#load"));
    return image;
  }
  const scenes = { base: makeScene(S.base, true) };
  Object.entries(OF).forEach(([lookId, look]) => {
    Object.entries(look.s || {}).forEach(([key, url]) => {
      scenes[lookId + ":" + key] = makeScene(url, false);
    });
  });

  function exactOutfitScene(top, bottom) {
    if (!top || !bottom || String(top.o) !== String(bottom.o)) return null;
    const lookId = String(top.o);
    const look = OF[lookId];
    const scene = look?.s?.TB;
    if (!look || !scene || !scenes[lookId + ":TB"]) return null;
    if (Number(look.t) !== Number(top.id) || Number(look.b) !== Number(bottom.id)) return null;
    return lookId + ":TB";
  }

  function setScene() {
    const selected = P.filter(product => W.has(Number(product.id)));
    const top = selected.find(product => product.slot === "top") || null;
    const bottom = selected.find(product => product.slot === "bottom") || null;
    const notice = $("#fit-scene-notice");
    const announce = message => {
      if (!notice) return;
      notice.textContent = message;
      notice.hidden = !message;
    };

    Object.values(scenes).forEach(image => {
      if (!image) return;
      image.classList.remove("on");
      image.style.clipPath = "";
      image.style.zIndex = "";
    });
    const activate = key => {
      const image = scenes[key];
      if (!image) return false;
      image.classList.add("on");
      return true;
    };

    if (!top && !bottom) {
      activate("base");
      announce("");
      return;
    }

    if (top && bottom) {
      const pairScene = exactOutfitScene(top, bottom);
      if (pairScene && activate(pairScene)) {
        announce("نمای واقعی ست کامل");
        return;
      }
      const preferred = Number(state.lastFittedId) === Number(bottom.id) ? bottom : top;
      const fallback = preferred === top ? bottom : top;
      const showSingle = item => activate(String(item.o) + ":" + (item.slot === "top" ? "T" : "B"));
      if (showSingle(preferred) || showSingle(fallback)) {
        announce("هر دو لباس انتخاب شده‌اند، اما تصویر واقعی این ترکیب ثبت نشده است؛ فقط نمای یکی از لباس‌ها نمایش داده می‌شود.");
        return;
      }
      activate("base");
      announce("تصویر این ترکیب در استودیو موجود نیست.");
      return;
    }

    const shown = top
      ? activate(String(top.o) + ":T")
      : activate(String(bottom.o) + ":B");
    if (!shown) activate("base");
    announce(shown ? "" : "تصویر این لباس روی مانکن موجود نیست.");
  }

  function updateCart(count) {
    state.cart = Math.max(0, Number(count) || 0);
    $("#cnt").textContent = fa(state.cart);
  }

  function flyToCart(source) {
    if (RM || !source || !source.getBoundingClientRect) return Promise.resolve();
    const target = $("#cartTarget");
    if (!target) return Promise.resolve();

    const start = source.getBoundingClientRect();
    const end = target.getBoundingClientRect();
    if (start.width < 1 || start.height < 1 || end.width < 1 || end.height < 1) {
      return Promise.resolve();
    }

    const isImage = source instanceof HTMLImageElement && (source.currentSrc || source.src);
    const element = document.createElement(isImage ? "img" : "div");
    if (isImage) {
      element.src = source.currentSrc || source.src;
      element.alt = "";
      element.decoding = "async";
    } else {
      element.textContent = "V.";
      element.classList.add("shop-cart-flight--fallback");
    }
    element.classList.add("shop-cart-flight");
    element.setAttribute("aria-hidden", "true");

    const width = Math.min(Math.max(start.width, 52), 144);
    const height = Math.min(Math.max(start.height, 64), 168);
    Object.assign(element.style, {
      position: "fixed",
      left: (start.left + (start.width - width) / 2) + "px",
      top: (start.top + (start.height - height) / 2) + "px",
      width: width + "px",
      height: height + "px"
    });
    document.body.appendChild(element);

    const dx = end.left + end.width / 2 - (start.left + start.width / 2);
    const dy = end.top + end.height / 2 - (start.top + start.height / 2);
    const arc = Math.min(105, window.innerHeight * 0.14);

    return new Promise(resolve => {
      let completed = false;
      const finish = () => {
        if (completed) return;
        completed = true;
        element.remove();
        resolve();
      };
      const timeline = gsap.timeline({ onComplete: finish, onInterrupt: finish });
      timeline
        .to(element, {
          x: dx * 0.46,
          y: dy * 0.42 - arc,
          rotation: -13,
          scale: 0.7,
          duration: 0.36,
          ease: "power2.out"
        })
        .to(element, {
          x: dx,
          y: dy,
          rotation: 9,
          scale: 0.12,
          opacity: 0.12,
          duration: 0.42,
          ease: "power3.in"
        }, "-=0.06");
    });
  }

  function pulseCartTarget() {
    if (RM) return;
    const target = $("#cartTarget");
    if (!target) return;

    gsap.killTweensOf(target);
    gsap.fromTo(target, { scale: 1 }, {
      scale: 1.12,
      duration: 0.2,
      repeat: 1,
      yoyo: true,
      ease: "back.out(2)",
      clearProps: "transform"
    });

    const badge = target.querySelector(".badge");
    if (badge) {
      gsap.killTweensOf(badge);
      gsap.fromTo(badge, { scale: 0.7 }, {
        scale: 1,
        duration: 0.2,
        ease: "back.out(3)",
        clearProps: "transform"
      });
    }
  }

  function refresh() {
    state.worn = new Set(W);
    setScene();
    const items = P.filter(product => W.has(Number(product.id)));
    $("#pcount").textContent = fa(items.length) + " تکه";
    $("#tot").textContent = items.length
      ? "جمع: " + fa(items.reduce((sum, product) => sum + Number(product.price || 0), 0)) + " " + (S.currency || "تومان")
      : "هنوز چیزی انتخاب نشده";
    $("#picks").innerHTML = items.map(product =>
      '<button type="button" class="on" data-r="' + Number(product.id) + '" title="درآوردن ' + escapeHtml(product.name) + '" aria-label="درآوردن ' + escapeHtml(product.name) + '">' +
        (product.img ? '<img src="' + escapeHtml(product.img) + '" alt="">' : '<span>V.</span>') +
      '</button>'
    ).join("");
    $("#addset").disabled = items.length === 0;
    render();
    renderRecommendations();
    renderComparison();
  }

  function renderRecommendations() {
    const root = $("#fit-recommend-grid");
    if (!root) return;

    const section = root.closest(".fit-recommendations");
    const chosenTop = P.find(product => product.slot === "top" && W.has(Number(product.id))) || null;
    const chosenBottom = P.find(product => product.slot === "bottom" && W.has(Number(product.id))) || null;

    // Recommendations are useful only when exactly one real garment is on the
    // mannequin. With zero or two selections, keep this panel out of the way.
    const selectedCount = Number(Boolean(chosenTop)) + Number(Boolean(chosenBottom));
    if (selectedCount !== 1) {
      if (section) section.hidden = true;
      root.replaceChildren();
      return;
    }

    const anchor = chosenTop || chosenBottom;
    const recommendationSlot = chosenTop ? "bottom" : "top";
    const candidates = P
      .filter(product => {
        if (product.slot !== recommendationSlot
          || W.has(Number(product.id))
          || Number(product.stock || 0) < 1
          || !product.img
          || !product.o) return false;

        const look = OF[String(product.o)];
        if (!look || !look.s || !look.s.TB) return false;

        // A recommendation is allowed only when the database has a complete
        // mannequin image for this exact pair of variants—not merely a shared
        // look ID or a separate top/bottom photo.
        return recommendationSlot === "bottom"
          ? Number(look.t) === Number(anchor.id) && Number(look.b) === Number(product.id)
          : Number(look.t) === Number(product.id) && Number(look.b) === Number(anchor.id);
      })
      .sort((a, b) => Number(b.rate || 0) - Number(a.rate || 0))
      .slice(0, 2);

    if (!candidates.length) {
      if (section) section.hidden = true;
      root.replaceChildren();
      return;
    }

    if (section) section.hidden = false;
    root.innerHTML = candidates.map(product =>
      '<button type="button" class="fit-rec-card" data-rec="' + Number(product.id) + '" aria-label="اضافه کردن ' + escapeHtml(product.name) + ' به ست">' +
        '<span class="fit-rec-thumb">' +
          '<img src="' + escapeHtml(product.img) + '" alt="' + escapeHtml(product.name) + '" loading="lazy" decoding="async">' +
        '</span>' +
        '<span class="fit-rec-copy">' +
          '<strong>' + escapeHtml(product.name) + '</strong>' +
          '<span>' + fa(product.price) + ' ' + escapeHtml(S.currency || "تومان") + '</span>' +
        '</span>' +
        '<span class="fit-rec-add" aria-hidden="true">+</span>' +
      '</button>'
    ).join("");
  }

  function persistComparison() {
    try {
      window.localStorage.setItem(COMPARE_KEY, JSON.stringify(comparison));
      return true;
    } catch (_) {
      return false;
    }
  }

  function saveComparison(slot) {
    if (!W.size) {
      showToast("اول لباس‌ها رو روی مانکن انتخاب کن.");
      return;
    }
    comparison[slot] = [...W].map(Number);
    const persisted = persistComparison();
    renderComparison();
    showToast(persisted
      ? "ترکیب " + slot + " برای مقایسه ذخیره شد."
      : "ترکیب برای همین صفحه ذخیره شد؛ ذخیره مرورگر در دسترس نیست.");
  }

  function renderComparison() {
    const root = $("#fit-compare-grid");
    if (!root) return;

    const card = slot => {
      const items = (comparison[slot] || [])
        .map(id => P.find(product => Number(product.id) === Number(id)))
        .filter(Boolean);
      const top = items.find(product => product.slot === "top");
      const bottom = items.find(product => product.slot === "bottom");
      const total = items.reduce((sum, product) => sum + Number(product.price || 0), 0);
      const piece = (product, label) => '<div class="fit-compare-piece">' +
        (product?.img ? '<img src="' + escapeHtml(product.img) + '" alt="" loading="lazy">' : '<span class="fit-compare-piece-empty">—</span>') +
        '<span><small>' + label + '</small><strong>' + escapeHtml(product?.name || "انتخاب نشده") + '</strong></span>' +
      '</div>';

      return '<article class="fit-compare-card">' +
        '<div class="fit-compare-card-head"><strong>استایل ' + slot + '</strong><span>' + (items.length ? fa(items.length) + ' تکه' : 'خالی') + '</span></div>' +
        (items.length
          ? piece(top, "بالاپوش") + piece(bottom, "پایین‌تنه") + '<div class="fit-compare-total">جمع <strong>' + fa(total) + ' ' + escapeHtml(S.currency || "تومان") + '</strong></div>'
          : '<p class="fit-compare-empty">برای ذخیره، لباس‌ها رو انتخاب کن و دکمه‌ی ' + slot + ' رو بزن.</p>') +
      '</article>';
    };

    root.innerHTML = card("A") + card("B");
    const buttonA = $("#saveCompareA");
    const buttonB = $("#saveCompareB");
    if (buttonA) buttonA.textContent = comparison.A.length ? "جایگزینی ترکیب A" : "ذخیره‌ی ترکیب A";
    if (buttonB) buttonB.textContent = comparison.B.length ? "جایگزینی ترکیب B" : "ذخیره‌ی ترکیب B";
  }

  function showToast(message) {
    const toast = $("#toast");
    toast.textContent = message;
    toast.classList.add("show");
    window.clearTimeout(showToast.timer);
    showToast.timer = window.setTimeout(() => toast.classList.remove("show"), 1700);
  }

  async function fly(source, fitY) {
    if (RM || !source || !source.getBoundingClientRect) return;
    const start = source.getBoundingClientRect(), end = stage.getBoundingClientRect();
    if (start.width < 1 || start.height < 1 || end.width < 1 || end.height < 1) return;
    const element = document.createElement("img");
    element.src = source.currentSrc || source.src;
    element.className = "fly";
    Object.assign(element.style, { left: start.left + "px", top: start.top + "px", width: start.width + "px", height: start.height + "px" });
    document.body.appendChild(element);
    const dx = end.left + end.width / 2 - (start.left + start.width / 2);
    const dy = end.top + end.height * Number(fitY || .38) - (start.top + start.height / 2);
    const animation = element.animate([
      { transform: "none", opacity: 1 },
      { transform: `translate(${dx * .5}px,${dy * .4 - 70}px) rotate(-16deg) scale(.62)`, offset: .5 },
      { transform: `translate(${dx}px,${dy}px) rotate(8deg) scale(.3)`, opacity: .15 }
    ], { duration: 780, easing: "cubic-bezier(.5,0,.25,1)" });
    await animation.finished.catch(() => undefined);
    element.remove();
  }

  async function tryOn(product, source, trigger = null) {
    if (busy || !product || !product.o || !product.slot || Number(product.stock || 0) < 1) return;
    if (trigger instanceof HTMLElement) fittingTrigger = trigger;
    fit.classList.add("open");
    fit.setAttribute("aria-hidden", "false");
    fit.setAttribute("aria-label", "اتاق پرو استودیویی VISION، لباس انتخاب‌شده روی مانکن");

    if (W.has(Number(product.id))) {
      W.delete(Number(product.id));
      refresh();
      return;
    }

    const oppositeSlot = product.slot === "top" ? "bottom" : "top";
    const opposite = P.find(item => item.slot === oppositeSlot && W.has(Number(item.id))) || null;
    const nextTop = product.slot === "top" ? product : opposite;
    const nextBottom = product.slot === "bottom" ? product : opposite;
    // Selecting an item must never be blocked by a missing combined image.
    // The scene resolver falls back to a real single-garment mannequin photo.

    busy = true;
    try {
      await wait(RM ? 0 : 260);
      await fly(source, product.fy);
      $("#load").classList.add("on");
      await wait(RM ? 0 : 700);
      // Keep the opposite slot selected even without an authored combined photo.
      // The scene resolver shows the last selected garment, never a fake composite.
      P.forEach(item => {
        if (item.slot === product.slot && W.has(Number(item.id))) W.delete(Number(item.id));
      });
      W.add(Number(product.id));
      state.lastFittedId = Number(product.id);
    } finally {
      $("#load").classList.remove("on");
      busy = false;
      refresh();
    }
  }

  function clearAll() {
    W.clear();
    state.lastFittedId = null;
    refresh();
  }

  async function addOne(productId, trigger) {
    if (trigger?.disabled) return;
    const wasDisabled = !!trigger?.disabled;
    if (trigger) trigger.disabled = true;
    let flight = Promise.resolve();

    try {
      const card = trigger?.closest(".shop-product-card");
      const source = card?.querySelector(".shop-pic img") || card?.querySelector(".shop-pic");
      flight = flyToCart(source);

      const response = await post(S.routes.cart, { variant_id: Number(productId), quantity: 1 });
      await flight;
      updateCart(response.count);
      pulseCartTarget();
      showToast(response.message || "به سبد خرید اضافه شد.");
    } catch (error) {
      await flight.catch(() => undefined);
      showToast(error.message || "افزودن به سبد خرید انجام نشد.");
    } finally {
      if (trigger?.isConnected) trigger.disabled = wasDisabled;
    }
  }

  document.addEventListener("click", event => {
    const button = event.target instanceof Element ? event.target.closest("button") : null;
    if (!button) return;
    const data = button.dataset;
    if (data.f) {
      const id = Number(data.f);
      const wasFavourite = state.fav.has(id);
      wasFavourite ? state.fav.delete(id) : state.fav.add(id);
      render();
      if (S.routes?.fav) {
        post(S.routes.fav + "/" + id).catch(() => {
          wasFavourite ? state.fav.add(id) : state.fav.delete(id);
          render();
          showToast("ذخیرهٔ علاقه‌مندی انجام نشد.");
        });
      }
    } else if (data.t) {
      const product = P.find(item => Number(item.id) === Number(data.t));
      const image = button.closest(".shop-product-card")?.querySelector(".shop-pic img");
      if (product && (!product.slot || !product.o || !OF[String(product.o)] || !OF[String(product.o)].s || !Object.keys(OF[String(product.o)].s).length)) {
        fit.classList.add("open");
        fit.setAttribute("aria-hidden", "false");
        showToast("برای پرو مجازی، این محصول را در استودیو به ست و تصویر مانکن وصل کن.");
      } else if (product) {
        tryOn(product, image, button);
      }
    } else if (data.rec) {
      const product = P.find(item => Number(item.id) === Number(data.rec));
      const image = button.closest(".fit-rec-card")?.querySelector(".fit-rec-thumb img");
      if (product) tryOn(product, image, button);
    } else if (data.r) {
      W.delete(Number(data.r));
      refresh();
    } else if (data.c) {
      addOne(Number(data.c), button);
    } else if (data.cat !== undefined) {
      state.cat = data.cat;
      state.visibleCount = 12;
      render();
    }
  });

  $("#close").addEventListener("click", () => {
    fit.classList.remove("open");
    fit.setAttribute("aria-hidden", "true");
    if (fittingTrigger?.isConnected) fittingTrigger.focus({ preventScroll: true });
  });
  $("#strip").addEventListener("click", clearAll);
  $("#saveCompareA").addEventListener("click", () => saveComparison("A"));
  $("#saveCompareB").addEventListener("click", () => saveComparison("B"));
  $("#clearCompare").addEventListener("click", () => {
    comparison.A = [];
    comparison.B = [];
    const persisted = persistComparison();
    renderComparison();
    showToast(persisted ? "استایل‌های مقایسه پاک شدند." : "استایل‌ها در همین صفحه پاک شدند.");
  });
  $("#addset").addEventListener("click", async event => {
    const button = event.currentTarget;
    if (button.disabled) return;
    const ids = [...W].map(Number);
    if (!ids.length) { showToast("اول یک لباس انتخاب کن."); return; }
    button.disabled = true;
    try {
      const response = await post(S.routes.cartMany, { variant_ids: ids });
      updateCart(response.count);
      showToast(response.message || "ست به سبد خرید اضافه شد.");
    } catch (error) {
      showToast(error.message || "افزودن ست به سبد خرید انجام نشد.");
    } finally {
      button.disabled = W.size === 0;
    }
  });

  $("#shop-load-more")?.addEventListener("click", () => { state.visibleCount += 12; render(); });
  $("#q").addEventListener("input", event => { state.q = event.target.value || ""; state.visibleCount = 12; render(); });
  $("#sort").addEventListener("change", event => { state.sort = event.target.value; state.visibleCount = 12; render(); });
  pr.addEventListener("input", event => { state.max = Number(event.target.value); state.visibleCount = 12; render(); });
  $("#reset").addEventListener("click", () => {
    state.visibleCount = 12; state.cat = C[0]; state.q = ""; state.sort = "new"; state.saleOnly = false; state.max = Number(S.max || 4000000);
    $("#q").value = ""; $("#sort").value = "new"; pr.value = state.max;
    render();
  });
  $("#theme").addEventListener("click", () => {
    const root = document.documentElement;
    root.dataset.theme = root.dataset.theme === "light" ? "dark" : "light";
  });
  document.addEventListener("keydown", event => {
    if (event.key === "Escape" && fit.classList.contains("open")) {
      fit.classList.remove("open");
      fit.setAttribute("aria-hidden", "true");
      if (fittingTrigger?.isConnected) fittingTrigger.focus({ preventScroll: true });
    }
  });


  $("#q").value = state.q;
  $("#sort").value = state.sort;
  if (pr) { pr.min = Number(S.min || 0); pr.max = Number(S.max || 4000000); pr.value = state.max; }
  updateCart(S.cart);
  render();
  refresh();

  if (S.routes?.cartSummary) {
    fetch(S.routes.cartSummary, { credentials: "same-origin", headers: { "Accept": "application/json" } })
      .then(response => response.ok ? response.json() : null)
      .then(response => { if (response && response.count !== undefined) updateCart(response.count); })
      .catch(() => undefined);
  }
})();
