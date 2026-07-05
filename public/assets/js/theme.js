(function () {
    const storageKey = 'evotech-theme';

    function getPreferredTheme() {
        const saved = localStorage.getItem(storageKey);
        if (saved === 'light' || saved === 'dark') {
            return saved;
        }

        return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches
            ? 'dark'
            : 'light';
    }

    function applyTheme(theme) {
        document.documentElement.dataset.theme = theme;
        document.querySelectorAll('[data-theme-toggle]').forEach(button => {
            const isDark = theme === 'dark';
            button.setAttribute('aria-pressed', isDark ? 'true' : 'false');
            button.setAttribute('title', isDark ? 'Passer en mode clair' : 'Passer en mode sombre');
            button.innerHTML = isDark
                ? '<i class="fa-solid fa-sun"></i>'
                : '<i class="fa-solid fa-moon"></i>';
        });
    }

    window.EvotechTheme = {
        init() {
            applyTheme(getPreferredTheme());
        },
        toggle() {
            const nextTheme = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
            localStorage.setItem(storageKey, nextTheme);
            applyTheme(nextTheme);
        }
    };

    document.addEventListener('DOMContentLoaded', () => {
        window.EvotechTheme.init();
        document.querySelectorAll('[data-theme-toggle]').forEach(button => {
            button.addEventListener('click', window.EvotechTheme.toggle);
        });
    });
})();
