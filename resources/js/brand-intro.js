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

        document.documentElement.classList.add("rmm-intro-active");

        const redirectTo = intro.dataset.redirectTo || "";
        let completed = false;

        const closeIntro = () => {
            if (completed || intro.classList.contains("is-leaving")) {
                return;
            }

            completed = true;
            intro.classList.add("is-leaving");

            document.documentElement.classList.remove("rmm-intro-active");

            if (redirectTo) {
                window.location.replace(redirectTo);
                return;
            }

            window.setTimeout(() => {
                intro.remove();
                window.dispatchEvent(
                    new CustomEvent("rmm:brand-intro:complete")
                );
            }, EXIT_DURATION);
        };

        window.setTimeout(closeIntro, DURATION);
    };

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
