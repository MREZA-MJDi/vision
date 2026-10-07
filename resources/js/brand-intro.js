/**
 * RMMajidi — Global Brand Intro
 *
 * Exact display duration:
 * 4000ms
 *
 * No external dependency.
 */

(() => {
    "use strict";

    const INTRO_ID = "rmm-brand-intro";
    const DURATION = 4000;
    const EXIT_DURATION = 520;

    const initBrandIntro = () => {
        const intro = document.getElementById(INTRO_ID);

        if (!intro) {
            return;
        }

        /*
         * Prevent the intro from blocking the page after
         * the initial display.
         */
        document.documentElement.classList.add("rmm-intro-active");

        const closeIntro = () => {
            if (!intro || intro.classList.contains("is-leaving")) {
                return;
            }

            intro.classList.add("is-leaving");

            window.setTimeout(() => {
                intro.remove();

                document.documentElement.classList.remove(
                    "rmm-intro-active"
                );

                window.dispatchEvent(
                    new CustomEvent("rmm:brand-intro:complete")
                );
            }, EXIT_DURATION);
        };

        /*
         * The complete intro lifecycle is exactly 4 seconds
         * before the exit transition begins.
         */
        window.setTimeout(closeIntro, DURATION);
    };

    /*
     * DOM may already be ready because Vite can load
     * the module dynamically.
     */
    if (document.readyState === "loading") {
        document.addEventListener(
            "DOMContentLoaded",
            initBrandIntro,
            { once: true }
        );
    } else {
        initBrandIntro();
    }
})();
