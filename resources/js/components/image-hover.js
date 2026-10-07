const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

const clamp = (value, min, max) => Math.min(max, Math.max(min, value));

const initImageHover = (container) => {
    if (container.dataset.imageHoverReady === 'true') return;

    const layers = [...container.querySelectorAll('.vision-image-hover__layer')];
    if (layers.length < 2) return;

    container.dataset.imageHoverReady = 'true';

    const stack = document.createElement('div');
    stack.className = 'vision-image-hover__stack';
    container.appendChild(stack);
    layers.forEach((layer) => stack.appendChild(layer));

    let hovered = false;
    let raf = 0;
    let pointer = { x: 0, y: 0 };
    let current = { rx: 0, ry: 0, x: 0, y: 0 };
    let animations = [];

    const cancelLayerAnimations = () => {
        animations.forEach((animation) => animation.cancel());
        animations = [];
    };

    const setLayerState = (hoveredState) => {
        cancelLayerAnimations();

        if (prefersReducedMotion.matches) {
            layers.forEach((layer, index) => {
                layer.style.opacity = index === 0 ? '1' : '0';
                layer.style.transform = index === 0
                    ? 'translate3d(0,0,0) scale(1)'
                    : 'translate3d(0,0,0) scale(.95)';
            });
            return;
        }

        layers.forEach((layer, index) => {
            if (index === 0) return;

            const scale = Math.max(1 - index * 0.06, 0.4);
            const rotation = index % 2 === 0 ? index * 15 : -index * 15;

            const from = hoveredState
                ? {
                    opacity: 0,
                    transform: 'translate3d(0,0,0) scale(.95) rotateZ(0deg)',
                }
                : {
                    opacity: 1,
                    transform: `translate3d(0,0,0) scale(${scale}) rotateZ(${rotation}deg)`,
                };

            const to = hoveredState
                ? {
                    opacity: 1,
                    transform: `translate3d(0,0,0) scale(${scale}) rotateZ(${rotation}deg)`,
                }
                : {
                    opacity: 0,
                    transform: 'translate3d(0,0,0) scale(.95) rotateZ(0deg)',
                };

            animations.push(
                layer.animate([from, to], {
                    duration: 800,
                    delay: index * 70,
                    easing: 'cubic-bezier(.22,1,.36,1)',
                    fill: 'forwards',
                })
            );
        });
    };

    const render = () => {
        raf = 0;

        if (!hovered || prefersReducedMotion.matches) return;

        const nx = pointer.x;
        const ny = pointer.y;

        current.rx += (-ny * 10 - current.rx) * 0.16;
        current.ry += (nx * 10 - current.ry) * 0.16;
        current.x += (nx * 10 - current.x) * 0.16;
        current.y += (ny * 10 - current.y) * 0.16;

        stack.style.transform =
            `translate3d(${current.x}px,${current.y}px,0) rotateX(${current.rx}deg) rotateY(${current.ry}deg)`;

        if (
            Math.abs(current.rx + ny * 10) > 0.03 ||
            Math.abs(current.ry - nx * 10) > 0.03 ||
            Math.abs(current.x - nx * 10) > 0.03 ||
            Math.abs(current.y - ny * 10) > 0.03
        ) {
            raf = requestAnimationFrame(render);
        }
    };

    const queueRender = () => {
        if (!raf) raf = requestAnimationFrame(render);
    };

    const reset = () => {
        hovered = false;

        if (raf) {
            cancelAnimationFrame(raf);
            raf = 0;
        }

        current = { rx: 0, ry: 0, x: 0, y: 0 };

        stack.style.transition =
            'transform 520ms cubic-bezier(.22,1,.36,1)';
        stack.style.transform =
            'translate3d(0,0,0) rotateX(0deg) rotateY(0deg)';

        window.setTimeout(() => {
            stack.style.transition = '';
        }, 540);

        setLayerState(false);
    };

    const enter = () => {
        hovered = true;
        stack.style.transition = '';
        setLayerState(true);
        queueRender();
    };

    const move = (event) => {
        if (!hovered || prefersReducedMotion.matches) return;

        const rect = container.getBoundingClientRect();

        pointer.x = clamp(
            ((event.clientX - rect.left) / rect.width - 0.5) * 2,
            -1,
            1
        );

        pointer.y = clamp(
            ((event.clientY - rect.top) / rect.height - 0.5) * 2,
            -1,
            1
        );

        queueRender();
    };

    container.addEventListener('pointerenter', enter);
    container.addEventListener('pointermove', move);
    container.addEventListener('pointerleave', reset);
    container.addEventListener('pointercancel', reset);
    container.addEventListener('focus', enter);
    container.addEventListener('blur', reset);

    prefersReducedMotion.addEventListener?.('change', reset);

    setLayerState(false);
};

export const initImageHoverComponents = () => {
    document.querySelectorAll('[data-image-hover]').forEach(initImageHover);
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initImageHoverComponents, { once: true });
} else {
    initImageHoverComponents();
}
