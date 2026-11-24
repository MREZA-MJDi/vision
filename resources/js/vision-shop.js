import { flyToCart, pulseCartTarget } from "./cart-motion";

(() => {
  const $ = (selector, parent = document) => parent.querySelector(selector);
  const studioData = $("#shop-studio-data");
  if (!studioData) return;

  const S = JSON.parse(studioData.textContent);
  const P = S.garments;
  const OF = S.looks;
  let showingSuggestion = false;
  let activeScene = null;
  const fa = n => Number(n || 0).toLocaleString("fa-IR");
  const RM = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  const CSRF = $('meta[name="csrf-token"]')?.content || "";
  const state = { fav: new Set(S.favs), lastFittedId: null };
  const selectionKey = "vision.fit.selection";
  const W = new Set();
  try {
    const stored = JSON.parse(sessionStorage.getItem(selectionKey) || "{}");
    for (const slot of ["top", "bottom"]) {
      const item = P.find(garment => garment.slot === slot && garment.id === stored[slot] && garment.stock > 0);
      if (item) W.add(item.id);
    }
    state.lastFittedId = stored.lastFittedId;
  } catch {
    // Selection can still be used on this page when session storage is unavailable.
  }

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
  const catalog = $("#catalog");
  const stage = $("#stage");
  const fit = $("#fit");
  const compact = window.matchMedia("(max-width: 1039px)");
  const background = [$(".vision-shop-header"), $(".shop-filter"), $(".shop-results"), $(".vision-footer")];
  const setFitLayout = () => {
    const modal = compact.matches && !fit.hidden;
    background.forEach(element => { if (element) element.inert = modal; });
    if (modal) {
      fit.setAttribute("role", "dialog");
      fit.setAttribute("aria-modal", "true");
    } else {
      fit.removeAttribute("role");
      fit.removeAttribute("aria-modal");
    }
    document.body.classList.toggle("shop-fit-modal", modal);
  };
  compact.addEventListener("change", setFitLayout);

  const escapeHtml = value => String(value ?? "").replace(/[&<>"']/g, char => ({
    "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;"
  })[char]);
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

  function syncCardActions() {
    document.querySelectorAll("#grid [data-f], #grid [data-t]").forEach(button => {
      const selected = button.dataset.f
        ? state.fav.has(Number(button.dataset.f))
        : W.has(Number(button.dataset.t));
      button.classList.toggle("on", selected);
      button.setAttribute("aria-pressed", String(selected));
    });
  }

  function makeScene(url, key) {
    if (!url) return null;
    const image = new Image();
    image.className = "mm";
    image.alt = "";
    image.setAttribute("aria-hidden", "true");
    const finish = (failed) => {
      if (activeScene !== key) return;
      $("#load").classList.remove("on");
      if (failed) {
        const notice = $("#fit-scene-notice");
        notice.textContent = "تصویر صحنه بارگذاری نشد.";
        notice.hidden = false;
      }
    };
    image.addEventListener("load", () => finish(false));
    image.addEventListener("error", () => finish(true));
    stage.insertBefore(image, $("#load"));
    image.src = url;
    return image;
  }
  const scenes = {};
  const sceneUrls = { base: S.base };
  Object.entries(OF).forEach(([lookId, look]) => {
    Object.entries(look.s || {}).forEach(([key, url]) => {
      sceneUrls[lookId + ":" + key] = url;
    });
  });

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

    const suggestion = !bottom && top ? S.suggestions[top.id] : null;
    const suggestionButton = $("#sugg");
    suggestionButton.hidden = !suggestion;
    if (!suggestion) showingSuggestion = false;
    suggestionButton.textContent = showingSuggestion ? "برگشت به لباس انتخاب‌شده" : "دیدن ست پیشنهادی";
    suggestionButton.setAttribute("aria-pressed", String(showingSuggestion));

    const activate = (key, url = sceneUrls[key]) => {
      if (!url) return false;
      if (fit.hidden) return true;
      activeScene = key;
      const image = scenes[key] ??= makeScene(url, key);
      $("#load").classList.toggle("on", !image.complete);
      Object.values(scenes).forEach(scene => scene.classList.toggle("on", scene === image));
      return true;
    };

    if (showingSuggestion && activate("suggestion:" + top.id, suggestion)) {
      announce("پیش‌نمایش پیشنهادی است. در سبد فقط لباس‌های انتخاب‌شده قرار می‌گیرند.");
      return;
    }

    if (!top && !bottom) {
      activate("base");
      announce("");
      return;
    }

    if (top && bottom) {
      const pairScene = S.combinations[top.id]?.[bottom.id];
      if (pairScene && activate("combined:" + top.id + ":" + bottom.id, pairScene)) {
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
    $("#cnt").textContent = fa(Math.max(0, Number(count) || 0));
  }

  function refresh() {
    setScene();
    const items = P.filter(product => W.has(Number(product.id)));
    try {
      sessionStorage.setItem(selectionKey, JSON.stringify({
        top: items.find(item => item.slot === "top")?.id,
        bottom: items.find(item => item.slot === "bottom")?.id,
        lastFittedId: state.lastFittedId
      }));
    } catch {
      // The current selection remains available in memory.
    }
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
    syncCardActions();
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

        return recommendationSlot === "bottom"
          ? Boolean(S.combinations[anchor.id]?.[product.id])
          : Boolean(S.combinations[product.id]?.[anchor.id]);
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

  function openFit(trigger) {
    if (trigger instanceof HTMLElement) fittingTrigger = trigger;
    const wasHidden = fit.hidden;
    fit.hidden = false;
    catalog.classList.add("fit-open");
    setFitLayout();
    if (wasHidden) {
      setScene();
      $("#close").focus({ preventScroll: true });
    }
  }

  async function tryOn(product, source, trigger = null) {
    if (busy || !product || !product.o || !product.slot || Number(product.stock || 0) < 1) return;
    showingSuggestion = false;
    openFit(trigger?.matches("[data-t]") ? trigger : null);

    if (W.has(Number(product.id))) {
      W.delete(Number(product.id));
      refresh();
      return;
    }

    busy = true;
    try {
      await fly(source, product.fy);
      // Keep the opposite slot selected even without an authored combined photo.
      // The scene resolver shows the last selected garment, never a fake composite.
      P.forEach(item => {
        if (item.slot === product.slot && W.has(Number(item.id))) W.delete(Number(item.id));
      });
      W.add(Number(product.id));
      state.lastFittedId = Number(product.id);
    } finally {
      busy = false;
      refresh();
    }
  }

  function clearAll() {
    showingSuggestion = false;
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
      flight = flyToCart(source, $("#cartTarget"));

      const response = await post(S.routes.cart, { variant_id: Number(productId), quantity: 1 });
      await flight;
      updateCart(response.count);
      pulseCartTarget($("#cartTarget"));
      showToast(response.message || "به سبد خرید اضافه شد.");
    } catch (error) {
      await flight.catch(() => undefined);
      showToast(error.message || "افزودن به سبد خرید انجام نشد.");
    } finally {
      if (trigger?.isConnected) trigger.disabled = wasDisabled;
    }
  }

  document.addEventListener("click", async event => {
    const button = event.target instanceof Element ? event.target.closest("button") : null;
    if (!button) return;
    const data = button.dataset;
    if (data.f) {
      const id = Number(data.f);
      const wasFavourite = state.fav.has(id);
      wasFavourite ? state.fav.delete(id) : state.fav.add(id);
      syncCardActions();
      button.disabled = true;
      try {
        await post(S.routes.fav + "/" + id);
      } catch {
        wasFavourite ? state.fav.add(id) : state.fav.delete(id);
        syncCardActions();
        showToast("ذخیرهٔ علاقه‌مندی انجام نشد.");
      } finally {
        button.disabled = false;
      }
    } else if (data.t) {
      const product = P.find(item => Number(item.id) === Number(data.t));
      const image = button.closest(".shop-product-card")?.querySelector(".shop-pic img");
      if (product) tryOn(product, image, button);
    } else if (data.rec) {
      const product = P.find(item => Number(item.id) === Number(data.rec));
      const image = button.closest(".fit-rec-card")?.querySelector(".fit-rec-thumb img");
      if (product) tryOn(product, image, button);
    } else if (data.r) {
      showingSuggestion = false;
      W.delete(Number(data.r));
      refresh();
    } else if (data.c) {
      addOne(Number(data.c), button);
    }
  });

  function closeFit() {
    fit.hidden = true;
    catalog.classList.remove("fit-open");
    setFitLayout();
    if (fittingTrigger?.isConnected) fittingTrigger.focus({ preventScroll: true });
  }

  $("#close").addEventListener("click", closeFit);
  $("#open-fit")?.addEventListener("click", event => openFit(event.currentTarget));
  $("#sugg").addEventListener("click", () => {
    showingSuggestion = !showingSuggestion;
    setScene();
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

  $("#pr").addEventListener("input", event => {
    $("#prv").textContent = fa(event.target.value) + " " + S.currency;
  });
  $("#theme").addEventListener("click", () => {
    const root = document.documentElement;
    root.dataset.theme = root.dataset.theme === "light" ? "dark" : "light";
  });
  document.addEventListener("keydown", event => {
    if (fit.hidden) return;
    if (event.key === "Escape") {
      closeFit();
    } else if (event.key === "Tab" && compact.matches) {
      const focusable = Array.from(fit.querySelectorAll("button:not(:disabled), summary"))
        .filter(element => element.getClientRects().length);
      const first = focusable[0];
      const last = focusable[focusable.length - 1];
      if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
      } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
      }
    }
  });

  refresh();

  if (S.routes?.cartSummary) {
    fetch(S.routes.cartSummary, { credentials: "same-origin", headers: { "Accept": "application/json" } })
      .then(response => response.ok ? response.json() : null)
      .then(response => { if (response && response.count !== undefined) updateCart(response.count); })
      .catch(() => undefined);
  }
})();
