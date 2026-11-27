import { flyToCart, pulseCartTarget } from "./cart-motion";

(() => {
  const $ = (selector, parent = document) => parent.querySelector(selector);
  const studioData = $("#shop-config");
  if (!studioData) return;

  const S = JSON.parse(studioData.textContent);
  let P = [];
  const canTry = product => Number(product?.stock || 0) > 0 || S.previewFit === true;
  const canBuySet = () => !S.previewOnly && W.size > 0 && [...W].every(id => Number(P.find(p => Number(p.id) === Number(id))?.stock || 0) > 0);
  let studio = null;
  let studioRequest = null;
  let activation = 0;
  let showingSuggestion = false;
  let activeScene = null;
  const persianNumber = new Intl.NumberFormat("fa-IR", { maximumFractionDigits: 0 });
  const fa = n => persianNumber.format(Number(n || 0));
  const RM = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  const CSRF = $('meta[name="csrf-token"]')?.content || "";
  const state = { fav: new Set(S.favs), lastFittedId: null };
  const selectionKey = "vision.fit.selection";
  const W = new Set();
  const COMPARE_KEY = "vision.fit.compare.v1";
  let comparison = { A: [], B: [] };

  function restoreStudioSelections() {
    try {
      const stored = JSON.parse(sessionStorage.getItem(selectionKey) || "{}");
      for (const slot of ["top", "bottom"]) {
        const item = P.find(garment => garment.slot === slot && garment.id === stored[slot] && canTry(garment));
        if (item) W.add(item.id);
      }
      state.lastFittedId = stored.lastFittedId;
    } catch {
      // Selection is still available for the current page.
    }

    try {
      const saved = JSON.parse(localStorage.getItem(COMPARE_KEY) || "{}");
      const clean = ids => Array.isArray(ids)
        ? ids.map(Number).filter(id => P.some(garment => garment.id === id))
        : [];
      comparison = { A: clean(saved.A), B: clean(saved.B) };
    } catch {
      // Comparison is still available for the current page.
    }
  }

  function loadStudio() {
    if (!studioRequest) {
      studioRequest = fetch(S.routes.studio, {
        credentials: "same-origin", headers: { Accept: "application/json" }
      }).then(async response => {
        if (!response.ok) throw new Error("اتاق پرو در دسترس نیست.");
        studio = await response.json();
        P = Array.isArray(studio.garments) ? studio.garments : [];
        restoreStudioSelections();
        renderGarmentPicker();
      }).catch(error => {
        studioRequest = null;
        throw error;
      });
    }
    return studioRequest;
  }
  let fittingFlight = null;
  let cartQueue = Promise.resolve();
  let cartUpdated = false;
  let addingSet = false;
  let fittingTrigger = null;
  const catalog = $("#catalog");
  const stage = $("#stage");
  const sceneLoading = $("#load");
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

  const smallScreen = window.matchMedia("(max-width: 760px)");
  const filterToggle = $("#shop-filter-toggle");
  const filterFields = $("#shop-filter-fields");
  let filtersOpen = false;
  const setFilterLayout = () => {
    filterFields.closest("form").dataset.expanded = String(filtersOpen);
    filterToggle.setAttribute("aria-expanded", String(!smallScreen.matches || filtersOpen));
  };
  filterToggle.addEventListener("click", () => {
    filtersOpen = !filtersOpen;
    setFilterLayout();
  });
  smallScreen.addEventListener("change", setFilterLayout);
  setFilterLayout();

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

  function addToCart(url, body) {
    const request = cartQueue.then(async () => {
      const response = await post(url, body);
      cartUpdated = true;
      updateCart(response.count);
      return response;
    });
    cartQueue = request.catch(() => undefined);
    return request;
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

  function setSceneLoading(loading) {
    sceneLoading.hidden = !loading;
    stage.setAttribute("aria-busy", String(loading));
  }

  function makeScene(url, key) {
    if (!url) return null;
    const image = new Image();
    image.className = "mm";
    image.alt = "";
    image.setAttribute("aria-hidden", "true");
    const finish = (failed) => {
      if (activeScene !== key || fittingFlight) return;
      setSceneLoading(false);
      if (failed) {
        const notice = $("#fit-scene-notice");
        notice.textContent = "تصویر صحنه بارگذاری نشد.";
        notice.hidden = false;
      }
    };
    image.addEventListener("load", () => finish(false));
    image.addEventListener("error", () => finish(true));
    stage.insertBefore(image, sceneLoading);
    image.src = url;
    return image;
  }
  const scenes = {};

  function renderGarmentPicker() {
    const picker = $("#fit-garment");
    const available = P.filter(product => product.scene && product.slot && canTry(product));
    picker.replaceChildren(new Option("لباس را انتخاب کنید", ""));
    for (const slot of ["top", "bottom"]) {
      const items = available.filter(product => product.slot === slot);
      if (!items.length) continue;
      const group = document.createElement("optgroup");
      group.label = slot === "top" ? "بالاپوش" : "پایین‌تنه";
      for (const product of items) group.append(new Option(product.title || product.name, String(product.id)));
      picker.append(group);
    }
    $("#fit-garment-picker").hidden = false;
    $("#fit-garment-empty").hidden = available.length > 0;
    picker.disabled = !available.length;
    $("#fit-choose").disabled = !picker.value;
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

    const suggestion = !bottom && top ? studio.suggestions[top.id] : null;
    const suggestionButton = $("#sugg");
    suggestionButton.hidden = !suggestion;
    if (!suggestion) showingSuggestion = false;
    suggestionButton.textContent = showingSuggestion ? "برگشت به لباس انتخاب‌شده" : "دیدن ست پیشنهادی";
    suggestionButton.setAttribute("aria-pressed", String(showingSuggestion));

    const activate = (key, url) => {
      if (!url) return false;
      if (fit.hidden) return true;
      activeScene = key;
      const image = scenes[key] ??= makeScene(url, key);
      if (image.complete && !image.naturalWidth) {
        delete scenes[key];
        image.remove();
        return false;
      }
      const reveal = () => {
        if (activeScene !== key || fit.hidden || fittingFlight) return;
        setSceneLoading(false);
        if (image.naturalWidth) {
          Object.values(scenes).forEach(scene => scene.classList.toggle("on", scene === image));
        } else {
          announce("تصویر این حالت بارگذاری نشد. نمای قبلی تا انتخاب دوباره حفظ می‌شود.");
          delete scenes[key];
          image.remove();
        }
      };
      if (image.complete) reveal();
      else {
        setSceneLoading(true);
        image.addEventListener("load", reveal, { once: true });
        image.addEventListener("error", reveal, { once: true });
      }
      return true;
    };

    if (showingSuggestion && activate("suggestion:" + top.id, suggestion)) {
      announce("پیش‌نمایش پیشنهادی است. در سبد فقط لباس‌های انتخاب‌شده قرار می‌گیرند.");
      return;
    }

    if (!top && !bottom) {
      const visible = activate("base", studio.base);
      announce(visible ? "" : "تصویر پایه مدل در دسترس نیست.");
      return;
    }

    if (top && bottom) {
      const pairScene = studio.combinations[top.id]?.[bottom.id];
      if (pairScene && activate("combined:" + top.id + ":" + bottom.id, pairScene)) {
        announce("نمای واقعی ست کامل");
        return;
      }
      const preferred = Number(state.lastFittedId) === Number(bottom.id) ? bottom : top;
      const fallback = preferred === top ? bottom : top;
      const showSingle = item => activate("variant:" + item.id, item.scene);
      if (showSingle(preferred) || showSingle(fallback)) {
        announce("هر دو لباس انتخاب شده‌اند، اما تصویر واقعی این ترکیب ثبت نشده است؛ فقط نمای یکی از لباس‌ها نمایش داده می‌شود.");
        return;
      }
      activate("base", studio.base);
      announce("تصویر این ترکیب در استودیو موجود نیست.");
      return;
    }

    const shown = top
      ? activate("variant:" + top.id, top.scene)
      : activate("variant:" + bottom.id, bottom.scene);
    if (!shown) activate("base", studio.base);
    announce(shown ? "" : "تصویر این لباس روی مانکن موجود نیست.");
  }

  function updateCart(count) {
    const quantity = fa(Math.max(0, Number(count) || 0));
    $("#cnt").textContent = quantity;
    $("#cartTarget").setAttribute("aria-label", `سبد خرید، ${quantity} کالا`);
  }

  function refresh() {
    if (!studio) return;
    if (!fittingFlight) setScene();
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
    $("#addset").disabled = addingSet || !canBuySet();
    if (S.previewOnly) $("#addset").textContent = "فقط پیش‌نمایش مدیر";
    $("#strip").disabled = items.length === 0;
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
          || !canTry(product)
          || !product.img
          || !product.scene) return false;

        return recommendationSlot === "bottom"
          ? Boolean(studio.combinations[anchor.id]?.[product.id])
          : Boolean(studio.combinations[product.id]?.[anchor.id]);
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

  function fly(source, fitY) {
    if (RM || !source || !source.getBoundingClientRect) return;
    const start = source.getBoundingClientRect(), end = stage.getBoundingClientRect();
    if (start.width < 1 || start.height < 1 || end.width < 1 || end.height < 1) return;
    if (typeof source.animate !== "function") return;
    const element = document.createElement("img");
    element.src = source.currentSrc || source.src;
    element.className = "fly";
    element.alt = "";
    element.setAttribute("aria-hidden", "true");
    Object.assign(element.style, { left: start.left + "px", top: start.top + "px", width: start.width + "px", height: start.height + "px" });
    document.body.appendChild(element);
    const dx = end.left + end.width / 2 - (start.left + start.width / 2);
    const dy = end.top + end.height * Number(fitY || .38) - (start.top + start.height / 2);
    const animation = element.animate([
      { transform: "none", opacity: 1 },
      { transform: `translate(${dx * .45}px,${dy * .4 - 55}px) rotate(-7deg) scale(.78)`, offset: .46 },
      { transform: `translate(${dx}px,${dy}px) rotate(0deg) scale(.34)`, opacity: .05 }
    ], { duration: 1050, easing: "cubic-bezier(.42,0,.32,1)" });
    fittingFlight = animation;
    $("#sugg").hidden = true;
    setSceneLoading(true);
    animation.finished.catch(() => undefined).then(() => {
      element.remove();
      if (fittingFlight !== animation) return;
      fittingFlight = null;
      setScene();
    });
  }

  function stopFittingFlight() {
    const animation = fittingFlight;
    fittingFlight = null;
    animation?.cancel();
  }

  async function openStudio(trigger, variantId = null) {
    if (!S.routes.studio) return;
    const request = ++activation;
    if (trigger) fittingTrigger = trigger;
    const wasHidden = fit.hidden;
    fit.hidden = false;
    catalog.classList.add("fit-open");
    setFitLayout();
    if (wasHidden) $("#close").focus({ preventScroll: true });

    const notice = $("#fit-scene-notice");
    notice.hidden = true;
    if (!studio) setSceneLoading(true);
    try {
      await loadStudio();
      if (activation !== request) return;
      $("#fit-retry").hidden = true;

      if (variantId !== null) {
        const product = P.find(item => item.id === variantId);
        if (product && canTry(product)) {
          const source = trigger?.closest(".shop-product-card")?.querySelector(".shop-pic img");
          tryOn(product, source);
          return;
        }
      }
      refresh();
      if (variantId !== null) {
        notice.textContent = "این لباس در حال حاضر برای پرو در دسترس نیست.";
        notice.hidden = false;
      }
    } catch {
      if (activation !== request) return;
      setSceneLoading(false);
      notice.textContent = "اتاق پرو بارگذاری نشد.";
      notice.hidden = false;
      $("#fit-retry").hidden = false;
    }
  }

  function tryOn(product, source) {
    if (!product?.scene || !product.slot || !canTry(product)) return;
    stopFittingFlight();
    showingSuggestion = false;

    if (W.has(Number(product.id))) {
      W.delete(Number(product.id));
      refresh();
      return;
    }

    const previous = P.find(item => item.slot === product.slot && W.has(Number(item.id)));
    if (previous) W.delete(Number(previous.id));
    W.add(Number(product.id));
    state.lastFittedId = Number(product.id);
    fly(source, product.fy);
    refresh();
  }

  function clearAll() {
    const resetHasFocus = document.activeElement === $("#strip");
    activation++;
    stopFittingFlight();
    showingSuggestion = false;
    W.clear();
    state.lastFittedId = null;
    refresh();
    if (resetHasFocus) $("#close").focus({ preventScroll: true });
  }

  async function addOne(productId, trigger) {
    if (trigger.disabled || S.previewOnly) return;
    trigger.disabled = true;
    try {
      const response = await addToCart(S.routes.cart, { variant_id: productId, quantity: 1 });
      const card = trigger.closest(".shop-product-card");
      const source = card?.querySelector(".shop-pic img") || card?.querySelector(".shop-pic");
      const target = $("#cartTarget");
      flyToCart(source, target).then(() => pulseCartTarget(target));
      showToast(response.message || "به سبد خرید اضافه شد.");
    } catch (error) {
      showToast(error.message || "افزودن به سبد خرید انجام نشد.");
    } finally {
      trigger.disabled = false;
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
      await openStudio(button, Number(data.t));
    } else if (data.rec) {
      const product = P.find(item => Number(item.id) === Number(data.rec));
      const image = button.closest(".fit-rec-card")?.querySelector(".fit-rec-thumb img");
      if (product) tryOn(product, image);
    } else if (data.r) {
      stopFittingFlight();
      showingSuggestion = false;
      W.delete(Number(data.r));
      refresh();
      ($("#picks button") || $("#close")).focus({ preventScroll: true });
    } else if (data.c) {
      addOne(Number(data.c), button);
    }
  });

  function closeFit() {
    activation++;
    stopFittingFlight();
    fit.hidden = true;
    catalog.classList.remove("fit-open");
    setFitLayout();
    if (fittingTrigger?.isConnected) fittingTrigger.focus({ preventScroll: true });
  }

  $("#close").addEventListener("click", closeFit);
  $("#open-fit")?.addEventListener("click", event => openStudio(event.currentTarget));
  document.querySelectorAll("[data-open-studio]").forEach(button => button.addEventListener("click", event => openStudio(event.currentTarget)));
  $("#fit-garment").addEventListener("change", event => { $("#fit-choose").disabled = !event.target.value; });
  $("#fit-choose").addEventListener("click", () => {
    const picker = $("#fit-garment");
    const product = P.find(item => Number(item.id) === Number(picker.value));
    if (!product) return;
    if (!W.has(Number(product.id))) tryOn(product, null);
    picker.value = "";
    $("#fit-choose").disabled = true;
  });
  $("#fit-retry").addEventListener("click", event => {
    event.currentTarget.hidden = true;
    openStudio(fittingTrigger);
  });
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
    if (!canBuySet()) { showToast("بعضی لباس‌ها موجود نیستند."); return; }
    addingSet = button.disabled = true;
    try {
      const response = await addToCart(S.routes.cartMany, { variant_ids: ids });
      showToast(response.message || "ست به سبد خرید اضافه شد.");
    } catch (error) {
      showToast(error.message || "افزودن ست به سبد خرید انجام نشد.");
    } finally {
      addingSet = false;
      button.disabled = !canBuySet();
    }
  });

  $("#pr").addEventListener("input", event => {
    $("#prv").textContent = fa(event.target.value) + " " + S.currency;
  });
  $("#theme").addEventListener("click", event => {
    const root = document.documentElement;
    const light = root.dataset.theme !== "light";
    root.dataset.theme = light ? "light" : "dark";
    event.currentTarget.setAttribute("aria-pressed", String(light));
  });
  document.addEventListener("keydown", event => {
    if (fit.hidden) return;
    if (event.key === "Escape") {
      closeFit();
    } else if (event.key === "Tab" && compact.matches) {
      const focusable = Array.from(fit.querySelectorAll("button:not(:disabled), select:not(:disabled), summary"))
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

  syncCardActions();

  if (S.routes?.cartSummary) {
    fetch(S.routes.cartSummary, { credentials: "same-origin", headers: { "Accept": "application/json" } })
      .then(response => response.ok ? response.json() : null)
      .then(response => { if (!cartUpdated && response?.count !== undefined) updateCart(response.count); })
      .catch(() => undefined);
  }
})();
