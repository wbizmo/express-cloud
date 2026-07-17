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

/* Sprint 15 enterprise control enhancement */
const normalizeText = (value) => value.trim().toLocaleLowerCase();

const sortSelectOptions = (select) => {
    const options = Array.from(select.options);
    const placeholders = options.filter(
        (option) => option.value === "" || option.disabled,
    );
    const values = options
        .filter((option) => option.value !== "" && !option.disabled)
        .sort((a, b) => a.text.localeCompare(b.text, undefined, {
            numeric: true,
            sensitivity: "base",
        }));

    select.replaceChildren(...placeholders, ...values);
    select.dataset.sortableSelect = "ready";
};

const enhanceSelects = (root = document) => {
    root.querySelectorAll("select:not([data-no-sort])").forEach((select) => {
        if (select.dataset.sortableSelect === "ready") return;
        sortSelectOptions(select);
        select.setAttribute("data-sortable-select", "ready");
    });
};

const enhanceCheckboxes = (root = document) => {
    root.querySelectorAll('input[type="checkbox"]:not([data-native-control])')
        .forEach((input) => {
            if (input.closest(".ec-toggle")) return;

            const wrapper = document.createElement("label");
            wrapper.className = "ec-toggle";
            input.parentNode.insertBefore(wrapper, input);
            wrapper.appendChild(input);

            const track = document.createElement("span");
            track.className = "ec-toggle-track";
            track.setAttribute("aria-hidden", "true");
            wrapper.appendChild(track);

            const text = document.createElement("span");
            text.className = "ec-toggle-label";
            text.textContent = input.dataset.label
                || input.getAttribute("aria-label")
                || "Enabled";
            wrapper.appendChild(text);
        });
};

const addBackButtons = (root = document) => {
    root.querySelectorAll("[data-page-header]").forEach((header) => {
        if (header.querySelector(".ec-back-button")) return;

        const button = document.createElement("button");
        button.type = "button";
        button.className = "ec-back-button";
        button.innerHTML = "← <span>Back</span>";
        button.addEventListener("click", () => {
            if (window.history.length > 1) {
                window.history.back();
            } else {
                window.location.assign("/");
            }
        });

        header.prepend(button);
    });
};

const enhanceCommercialControls = (root = document) => {
    enhanceSelects(root);
    enhanceCheckboxes(root);
    addBackButtons(root);
};

document.addEventListener("DOMContentLoaded", () => {
    enhanceCommercialControls();
});

new MutationObserver((mutations) => {
    for (const mutation of mutations) {
        mutation.addedNodes.forEach((node) => {
            if (node instanceof HTMLElement) {
                enhanceCommercialControls(node);
            }
        });
    }
}).observe(document.documentElement, {
    childList: true,
    subtree: true,
});
