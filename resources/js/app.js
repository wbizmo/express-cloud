import { createIcons, icons } from 'lucide';

const renderIcons = () => {
    createIcons({
        icons,
        attrs: {
            'aria-hidden': 'true',
            'stroke-width': 1.8,
        },
    });
};

document.addEventListener('DOMContentLoaded', renderIcons);
document.addEventListener('livewire:navigated', renderIcons);
document.addEventListener('express-cloud:icons-refresh', renderIcons);

document.addEventListener('alpine:init', () => {
    Alpine.store('shell', {
        sidebarCollapsed: localStorage.getItem('ec.sidebar.collapsed') === 'true',
        mobileOpen: false,

        toggleSidebar() {
            this.sidebarCollapsed = !this.sidebarCollapsed;
            localStorage.setItem(
                'ec.sidebar.collapsed',
                String(this.sidebarCollapsed),
            );

            requestAnimationFrame(renderIcons);
        },

        openMobile() {
            this.mobileOpen = true;
        },

        closeMobile() {
            this.mobileOpen = false;
        },
    });

    Alpine.store('toasts', {
        items: [],

        push({
            title,
            message = '',
            type = 'info',
            duration = 4500,
            persistent = false,
        }) {
            const id = crypto.randomUUID();

            this.items.push({
                id,
                title,
                message,
                type,
                persistent,
            });

            if (!persistent) {
                window.setTimeout(() => this.remove(id), duration);
            }

            requestAnimationFrame(renderIcons);

            return id;
        },

        remove(id) {
            this.items = this.items.filter((toast) => toast.id !== id);
        },
    });

    Alpine.store('page', {
        loading: false,

        start() {
            this.loading = true;
        },

        stop() {
            this.loading = false;
        },
    });
});

window.ExpressCloud = {
    toast(payload) {
        Alpine.store('toasts').push(payload);
    },

    refreshIcons: renderIcons,
};
