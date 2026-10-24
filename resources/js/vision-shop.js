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
  const state = { cat: C[0], q: S.initialQuery || "", sort: "new", max: Number(S.max || 4000000), fav: new Set(), worn: new Set(), cart: 0 };
  const W = new Set();
  let busy = false, currentLook = null, suggested = false;
  const pr = $("#pr"), grid = $("#grid"), stage = $("#stage"), fit = $("#fit");

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

  const formatMoney = amount => fa(amount) + " <small>" + escapeHtml(S.currency || "تومان") + "</small>";

  function list() {
    let products = P.filter(product =>
      (state.cat === C[0] || product.cat === state.cat)
      && String(product.name || "").toLocaleLowerCase("fa").includes(state.q.toLocaleLowerCase("fa"))
      && Number(product.price || 0) <= state.max
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
    grid.innerHTML = products.length ? products.map(product => {
      const canFit = !!(product.slot && product.o && OF[String(product.o)] && OF[String(product.o)].s);
      const image = product.img
        ? '<img src="' + escapeHtml(product.img) + '" alt="' + escapeHtml(product.name) + '" loading="lazy" decoding="async">'
        : '<span class="shop-pic-empty" aria-label="تصویر محصول ثبت نشده">V.</span>';
      return '<article class="shop-product-card" data-product="' + Number(product.id) + '">' +
        '<div class="shop-product-side">' +
          '<button type="button" class="fav ' + (state.fav.has(Number(product.id)) ? "on" : "") + '" data-f="' + Number(product.id) + '" aria-pressed="' + state.fav.has(Number(product.id)) + '" aria-label="ذخیرهٔ ' + escapeHtml(product.name) + '" title="علاقه‌مندی">' +
            '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.5 8.8c0 5.1-8.5 10-8.5 10s-8.5-4.9-8.5-10A4.6 4.6 0 0 1 12 6.3a4.6 4.6 0 0 1 8.5 2.5Z"/></svg>' +
          '</button>' +
          '<button type="button" class="try ' + (state.worn.has(Number(product.id)) ? "on" : "") + '" data-t="' + Number(product.id) + '" aria-pressed="' + state.worn.has(Number(product.id)) + '" aria-label="امتحان ' + escapeHtml(product.name) + ' روی مانکن" title="امتحان روی مانکن" ' + (!canFit ? "disabled" : "") + '>' +
            '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 7V5.4a2.2 2.2 0 1 0-2.2-2.2"/><path d="m12 8.2-8.5 4.9a1.8 1.8 0 0 0 .9 3.4h15.2a1.8 1.8 0 0 0 .9-3.4L12 8.2Z"/></svg>' +
          '</button>' +
        '</div>' +
        (product.rate !== null && product.rate !== undefined ? '<span class="shop-rate">★ ' + fa(product.rate) + '</span>' : "") +
        '<a class="shop-pic" href="' + escapeHtml(product.url || "#") + '" aria-label="مشاهدهٔ ' + escapeHtml(product.name) + '">' + image + '</a>' +
        '<a class="shop-product-name" href="' + escapeHtml(product.url || "#") + '">' + escapeHtml(product.name) + '</a>' +
        '<div class="shop-product-price">' + formatMoney(product.price) +
          (product.old && Number(product.old) > Number(product.price) ? '<s>' + fa(product.old) + '</s><em>حراج</em>' : "") +
        '</div>' +
        '<button type="button" class="shop-cart-add" data-c="' + Number(product.id) + '" aria-label="افزودن ' + escapeHtml(product.name) + ' به سبد خرید" ' + (Number(product.stock || 0) < 1 ? "disabled" : "") + '>' +
          '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 8h16l-1.2 12H5.2L4 8Z"/><path d="M8.5 8V6a3.5 3.5 0 0 1 7 0v2M12 12v4m-2-2h4"/></svg>' +
        '</button>' +
      '</article>';
    }).join("") : '<div class="shop-empty"><strong>چیزی برای این جست‌وجو پیدا نشد.</strong>عبارت یا فیلتر را عوض کن.</div>';

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
  if (scenes.base) $("#stageFallback")?.setAttribute("hidden", "hidden");

  function sceneKey() {
    if (!W.size || currentLook === null) return "base";
    const look = OF[String(currentLook)];
    if (!look) return "base";
    const hasTop = !!look.t && W.has(Number(look.t));
    const hasBottom = !!look.b && W.has(Number(look.b));
    let key = hasTop && hasBottom && look.s?.TB ? "TB" : hasTop ? "T" : hasBottom ? "B" : "base";
    if (suggested && look.s?.S && hasTop) key = "S";
    return key === "base" ? "base" : String(currentLook) + ":" + key;
  }

  function setScene() {
    const activeKey = sceneKey();
    Object.entries(scenes).forEach(([key, image]) => image?.classList.toggle("on", key === activeKey));
  }

  function updateCart(count) {
    state.cart = Math.max(0, Number(count) || 0);
    $("#cnt").textContent = fa(state.cart);
  }

  function refresh() {
    if (!W.size) { currentLook = null; suggested = false; }
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
    const look = currentLook !== null ? OF[String(currentLook)] : null;
    const suggestion = $("#sugg");
    suggestion.classList.toggle("show", !!(look?.s?.S && look?.t && W.has(Number(look.t))));
    suggestion.classList.toggle("on", suggested);
    suggestion.textContent = suggested ? "بازگشت به همین لباس" : "دیدن ست پیشنهادی";
    $("#addset").disabled = items.length === 0;
    render();
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

  async function tryOn(product, source) {
    if (busy || !product || !product.o || !product.slot) return;
    fit.classList.add("open");
    fit.setAttribute("aria-hidden", "false");
    if (W.has(Number(product.id))) {
      W.delete(Number(product.id));
      suggested = false;
      refresh();
      return;
    }
    busy = true;
    try {
      await wait(RM ? 0 : 260);
      await fly(source, product.fy);
      $("#load").classList.add("on");
      await wait(RM ? 0 : 850);
      if (String(currentLook) !== String(product.o)) {
        W.clear();
        currentLook = product.o;
      } else {
        // Keep one top and one bottom at a time, as in the approved demo.
        const look = OF[String(product.o)];
        if (product.slot === "top" && look?.t) W.delete(Number(look.t));
        if (product.slot === "bottom" && look?.b) W.delete(Number(look.b));
      }
      W.add(Number(product.id));
      suggested = false;
    } finally {
      $("#load").classList.remove("on");
      busy = false;
      refresh();
    }
  }

  function clearAll() {
    W.clear();
    currentLook = null;
    suggested = false;
    refresh();
  }

  async function addOne(productId) {
    try {
      const response = await post(S.routes.cart, { variant_id: Number(productId), quantity: 1 });
      updateCart(response.count);
      showToast(response.message || "به سبد خرید اضافه شد.");
    } catch (error) {
      showToast(error.message || "افزودن به سبد خرید انجام نشد.");
    }
  }

  document.addEventListener("click", event => {
    const button = event.target instanceof Element ? event.target.closest("button") : null;
    if (!button) return;
    const data = button.dataset;
    if (data.f) {
      const id = Number(data.f);
      state.fav.has(id) ? state.fav.delete(id) : state.fav.add(id);
      try { window.localStorage.setItem("vision-shop-favourites", JSON.stringify([...state.fav])); } catch (_) {}
      render();
    } else if (data.t) {
      const product = P.find(item => Number(item.id) === Number(data.t));
      const image = button.closest(".shop-product-card")?.querySelector(".shop-pic img");
      if (product) tryOn(product, image);
    } else if (data.r) {
      W.delete(Number(data.r));
      suggested = false;
      refresh();
    } else if (data.c) {
      addOne(Number(data.c));
    } else if (data.cat !== undefined) {
      state.cat = data.cat;
      render();
    }
  });

  $("#close").addEventListener("click", () => {
    fit.classList.remove("open");
    fit.setAttribute("aria-hidden", "true");
  });
  $("#strip").addEventListener("click", clearAll);
  $("#sugg").addEventListener("click", () => { suggested = !suggested; refresh(); });
  $("#addset").addEventListener("click", async () => {
    const ids = [...W].map(Number);
    if (!ids.length) { showToast("اول یک لباس انتخاب کن."); return; }
    try {
      const response = await post(S.routes.cartMany, { variant_ids: ids });
      updateCart(response.count);
      showToast(response.message || "ست به سبد خرید اضافه شد.");
    } catch (error) {
      showToast(error.message || "افزودن ست به سبد خرید انجام نشد.");
    }
  });

  $("#q").addEventListener("input", event => { state.q = event.target.value || ""; render(); });
  $("#sort").addEventListener("change", event => { state.sort = event.target.value; render(); });
  pr.addEventListener("input", event => { state.max = Number(event.target.value); render(); });
  $("#reset").addEventListener("click", () => {
    state.cat = C[0]; state.q = ""; state.sort = "new"; state.max = Number(S.max || 4000000);
    $("#q").value = ""; $("#sort").value = "new"; pr.value = state.max;
    render();
  });
  $("#theme").addEventListener("click", () => {
    const root = document.documentElement;
    root.dataset.theme = root.dataset.theme === "light" ? "dark" : "light";
    try { window.localStorage.setItem("vision-shop-theme", root.dataset.theme); } catch (_) {}
  });
  document.addEventListener("keydown", event => {
    if (event.key === "Escape" && fit.classList.contains("open")) {
      fit.classList.remove("open");
      fit.setAttribute("aria-hidden", "true");
    }
  });

  try {
    const theme = window.localStorage.getItem("vision-shop-theme");
    if (theme === "light" || theme === "dark") document.documentElement.dataset.theme = theme;
    const favs = JSON.parse(window.localStorage.getItem("vision-shop-favourites") || "[]");
    if (Array.isArray(favs)) favs.forEach(value => { if (Number.isFinite(Number(value))) state.fav.add(Number(value)); });
  } catch (_) {}

  $("#q").value = state.q;
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
