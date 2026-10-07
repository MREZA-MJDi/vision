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
    let animation = null;

    const setLayerState = (hoveredState) => {
        if (prefersReducedMotion.matches) {
            layers.forEach((layer, index) => {
                layer.style.opacity = index === 0 ? '1' : '0';
                layer.style.transform = index === 0
                    ? 'translate3d(0,0,0) scale(1)'
                    : 'translate3d(0,0,0) scale(.95)';
            });
            return;
        }

        if (animation) animation.cancel();

        layers.forEach((layer, index) => {
            if (index === 0) return;

            const scale = Math.max(1 - index * 0.06, 0.4);
            const rotation = index % 2 === 0 ? index * 15 : -index * 15;

            layer.animate(
                [
                    {
                        opacity: hoveredState ? 1 : 0,
                        transform: hoveredState
                            ? `translate3d(0,0,0) scale(${scale}) rotateZ(${rotation}deg)`
                            : 'translate3d(0,0,0) scale(.95) rotateZ(0deg)',
                    },
                    {
                        opacity: hoveredState ? 1 : 0,
                        transform: hoveredState
                            ? `translate3d(0,0,0) scale(${scale}) rotateZ(${rotation}deg)`
                            : 'translate3d(0,0,0) scale(.95) rotateZ(0deg)',
                    },
                ],
                {
                    duration: 800,
                    delay: index * 70,
                    easing: 'cubic-bezier(.22,1,.36,1)',
                    fill: 'forwards',
                }
            );
        });
    };

    const render = () => {
        raf = 0;

        if (!hovered || prefersReducedMotion.matches) return;

        const nx = pointer.x;
        const ny = pointer.y;

        current.rx += (-ny * 22 - current.rx) * 0.16;
        current.ry += (nx * 22 - current.ry) * 0.16;
        current.x += (nx * 26 - current.x) * 0.16;
        current.y += (ny * 26 - current.y) * 0.16;

        stack.style.transform =
            `translate3d(${current.x}px,${current.y}px,0) rotateX(${current.rx}deg) rotateY(${current.ry}deg)`;

        if (
            Math.abs(current.rx + ny * 22) > 0.05 ||
            Math.abs(current.ry - nx * 22) > 0.05
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

    container.addEventListener('focus', enter);
    container.addEventListener('blur', reset);

    container.addEventListener('pointercancel', reset);

    prefersReducedMotion.addEventListener?.('change', () => {
        reset();
    });

    setLayerState(false);
};

export const initImageHoverComponents = () => {
    document
        .querySelectorAll('[data-image-hover]')
        .forEach(initImageHover);
};

if (document.readyState === 'loading') {
    document.addEventListener(
        'DOMContentLoaded',
        initImageHoverComponents,
        { once: true }
    );
} else {
    initImageHoverComponents();
}
