import './bootstrap';
import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';

createInertiaApp({
    resolve: (name) => {
        const pages = import.meta.glob('./Pages/**/*.tsx', { eager: true });
        const page = pages[`./Pages/${name}.tsx`];
        return page.default;
    },
    setup({ el, App, props }) {
        const root = createRoot(el);
        root.render(<App {...props} />);
    },
});
