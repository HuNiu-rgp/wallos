import { computed, ref } from 'vue';

const defaultTheme = 'light';
const storageKey = 'wallos.theme';
const storedTheme = typeof window !== 'undefined' ? window.localStorage.getItem(storageKey) : null;
const currentTheme = ref(storedTheme || defaultTheme);

function applyTheme(theme) {
    if (typeof document === 'undefined') {
        return;
    }

    document.documentElement.classList.toggle('dark', theme === 'dark');
    document.documentElement.style.colorScheme = theme === 'dark' ? 'dark' : 'light';
}

applyTheme(currentTheme.value);

export function useTheme() {
    const setTheme = (theme) => {
        currentTheme.value = theme;
        window.localStorage.setItem(storageKey, theme);
        applyTheme(theme);
    };

    const toggleTheme = () => setTheme(currentTheme.value === 'dark' ? 'light' : 'dark');

    return {
        currentTheme: computed(() => currentTheme.value),
        isDark: computed(() => currentTheme.value === 'dark'),
        setTheme,
        toggleTheme,
    };
}
