import { gsap } from "gsap";

export const prefersReducedMotion = () => window.matchMedia("(prefers-reduced-motion: reduce)").matches;

export function flyToCart(source, target) {
  if (prefersReducedMotion() || !source || !target) return Promise.resolve();

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

export function pulseCartTarget(target) {
  if (prefersReducedMotion() || !target) return;

  gsap.killTweensOf(target);
  gsap.fromTo(target, { scale: 1 }, {
    scale: 1.12,
    duration: 0.2,
    repeat: 1,
    yoyo: true,
    ease: "back.out(2)",
    clearProps: "transform"
  });

  const badge = target.querySelector(".badge, [data-cart-count]");
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
