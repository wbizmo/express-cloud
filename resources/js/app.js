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

/* Post-live searchable product and barcode controls */
const productFinder = (root) => {
    const input = root.querySelector('[data-product-query]');
    const hidden = root.querySelector('[data-product-id]');
    const results = root.querySelector('[data-product-results]');
    if (!input || !hidden || !results || root.dataset.productFinderReady) return;
    root.dataset.productFinderReady = 'true';

    const products = JSON.parse(root.querySelector('[data-products-json]').textContent || '[]');
    const normalize = (value) => String(value || '').trim().toLowerCase();

    const choose = (product) => {
        hidden.value = product.id;
        input.value = `${product.name} — ${product.sku}${product.barcode ? ` — ${product.barcode}` : ''}`;
        results.replaceChildren();
        results.hidden = true;
        input.dispatchEvent(new CustomEvent('product-selected', { bubbles: true, detail: product }));
    };

    const render = () => {
        const term = normalize(input.value);
        hidden.value = '';
        const matches = products.filter((product) => [product.name, product.sku, product.barcode]
            .some((value) => normalize(value).includes(term))).slice(0, 12);
        results.replaceChildren();
        matches.forEach((product) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'ec-product-result';
            button.textContent = `${product.name} — ${product.sku}${product.barcode ? ` — ${product.barcode}` : ''}`;
            button.addEventListener('click', () => choose(product));
            results.appendChild(button);
        });
        results.hidden = matches.length === 0;
        const exact = matches.find((product) => [product.sku, product.barcode]
            .some((value) => normalize(value) === term));
        if (exact) choose(exact);
    };

    input.addEventListener('input', render);
    input.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            render();
            results.querySelector('button')?.click();
        }
    });
};

const enhanceProductFinders = (root = document) => {
    root.querySelectorAll('[data-product-finder]').forEach(productFinder);
};

document.addEventListener('DOMContentLoaded', () => enhanceProductFinders());
new MutationObserver((mutations) => mutations.forEach((mutation) => mutation.addedNodes.forEach((node) => {
    if (node instanceof HTMLElement) enhanceProductFinders(node);
}))).observe(document.documentElement, { childList: true, subtree: true });

document.addEventListener('alpine:init', () => {
    Alpine.data('productScanner', ({ endpoint, context, branchField }) => ({
        term: '', results: [], selected: null, selectedId: '', open: false, loading: false,
        branchId() {
            const field = document.querySelector(`[name="${branchField}"]`);
            return field ? field.value : '';
        },
        async search() {
            this.selected = null; this.selectedId = '';
            if (!this.term.trim() || !this.branchId()) { this.results = []; this.open = false; return; }
            this.loading = true;
            try {
                const url = new URL(endpoint, window.location.origin);
                url.searchParams.set('q', this.term.trim());
                url.searchParams.set('branch_id', this.branchId());
                url.searchParams.set('context', context);
                const response = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
                if (!response.ok) { this.results = []; this.open = false; return; }
                this.results = (await response.json()).data || [];
                this.open = true;
            } finally { this.loading = false; }
        },
        chooseExactOrFirst() {
            const q = this.term.trim().toLowerCase();
            const item = this.results.find(i => (i.barcode || '').toLowerCase() === q || (i.sku || '').toLowerCase() === q) || this.results[0];
            if (item) this.select(item); else this.search();
        },
        select(item) {
            this.selected = item; this.selectedId = item.id; this.term = item.name; this.open = false;
            this.$dispatch('product-selected', { ...item });
        },
        close() { this.open = false; },
    }));
});

/**
 * Progressive quantity controls for every server-rendered product operation.
 * Applies to sales, stock intake, transfers, adjustments, purchasing, returns,
 * and any future product form using a conventional quantity/qty/units field.
 */
(function installProductQuantitySteppers() {
    const quantityName = /(^|\[|_)(quantity|qty|units|milliunits)(\]|_|$)/i;

    function precision(value) {
        const text = String(value ?? '');
        return text.includes('.') ? text.split('.')[1].length : 0;
    }

    function enhance(input) {
        if (!(input instanceof HTMLInputElement)) return;
        if (input.dataset.quantityStepperReady === '1') return;
        if (!input.name || !quantityName.test(input.name)) return;
        if (input.type === 'hidden' || input.disabled || input.readOnly) return;
        if (!['number', 'text', 'search'].includes(input.type)) return;

        input.dataset.quantityStepperReady = '1';
        input.inputMode = input.inputMode || 'decimal';

        const wrapper = document.createElement('div');
        wrapper.className = 'ec-quantity-stepper';
        wrapper.setAttribute('role', 'group');
        wrapper.setAttribute('aria-label', 'Quantity controls');

        const minus = document.createElement('button');
        minus.type = 'button';
        minus.className = 'ec-quantity-stepper__button';
        minus.setAttribute('aria-label', 'Decrease quantity');
        minus.textContent = '−';

        const plus = document.createElement('button');
        plus.type = 'button';
        plus.className = 'ec-quantity-stepper__button';
        plus.setAttribute('aria-label', 'Increase quantity');
        plus.textContent = '+';

        input.parentNode.insertBefore(wrapper, input);
        wrapper.append(minus, input, plus);
        input.classList.add('ec-quantity-stepper__input');

        const update = (direction) => {
            const configuredStep = Number(input.step);
            const step = Number.isFinite(configuredStep) && configuredStep > 0
                ? configuredStep
                : /milliunits/i.test(input.name) ? 1000 : 1;
            const minimum = input.min !== '' ? Number(input.min) : 0;
            const maximum = input.max !== '' ? Number(input.max) : Number.POSITIVE_INFINITY;
            const current = Number(input.value || 0);
            const next = Math.min(maximum, Math.max(minimum, current + (direction * step)));
            const places = Math.max(precision(step), precision(input.value));

            input.value = places > 0 ? next.toFixed(places) : String(Math.round(next));
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.dispatchEvent(new Event('change', { bubbles: true }));
        };

        minus.addEventListener('click', () => update(-1));
        plus.addEventListener('click', () => update(1));
    }

    function scan(root = document) {
        root.querySelectorAll('input[name]').forEach(enhance);
    }

    document.addEventListener('DOMContentLoaded', () => scan());
    document.addEventListener('alpine:initialized', () => scan());

    new MutationObserver((mutations) => {
        for (const mutation of mutations) {
            for (const node of mutation.addedNodes) {
                if (!(node instanceof Element)) continue;
                if (node.matches?.('input[name]')) enhance(node);
                scan(node);
            }
        }
    }).observe(document.documentElement, { childList: true, subtree: true });
})();

/* Sprint 1: consistent submit feedback without changing controller behaviour. */
function installSubmitLoadingState() {
    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) return;

        form.querySelectorAll('[data-submit-button]').forEach((button) => {
            if (!(button instanceof HTMLButtonElement) || button.disabled) return;
            button.dataset.originalHtml = button.innerHTML;
            button.disabled = true;
            button.setAttribute('aria-busy', 'true');
            button.innerHTML = '<span class="ec-spinner" aria-hidden="true"></span><span>Working…</span>';
        });
    });

    window.addEventListener('pageshow', () => {
        document.querySelectorAll('[data-submit-button][aria-busy="true"]').forEach((button) => {
            if (!(button instanceof HTMLButtonElement)) return;
            button.disabled = false;
            button.removeAttribute('aria-busy');
            if (button.dataset.originalHtml) {
                button.innerHTML = button.dataset.originalHtml;
                delete button.dataset.originalHtml;
            }
        });
    });
}

installSubmitLoadingState();

// EXPRESS CLOUD SPRINT 2 POS:START
document.addEventListener('alpine:init', () => {
    Alpine.data('posSale', (catalog, stockMap, paymentMethods, initialBranch = '') => ({
        catalog,
        stockMap,
        paymentMethods,
        query: '',
        branchId: initialBranch,
        saleType: 'pos',
        cart: [],
        payments: [],
        customerId: '',
        notes: '',
        mobileCartOpen: false,
        submitting: false,
        scanMessage: '',

        init() {
            this.resetPayments();
            this.$nextTick(() => this.$refs.search?.focus());
        },

        get filteredProducts() {
            const q = this.query.trim().toLowerCase();
            if (!q) return this.catalog;
            return this.catalog.filter((product) =>
                product.name.toLowerCase().includes(q)
                || product.sku.toLowerCase().includes(q)
                || String(product.barcode || '').toLowerCase().includes(q)
            );
        },

        stockFor(product) {
            if (!product.track_inventory) return null;
            return Number(this.stockMap[`${this.branchId}|${product.id}`] || 0) / 1000;
        },

        canAdd(product) {
            return this.branchId && (!product.track_inventory || this.stockFor(product) > 0);
        },

        addProduct(product) {
            if (!this.canAdd(product)) return;
            const existing = this.cart.find((line) => line.id === product.id);
            const stock = this.stockFor(product);
            if (existing) {
                if (product.track_inventory && existing.quantity >= stock) return;
                existing.quantity += 1;
            } else {
                this.cart.push({ ...product, quantity: 1, discount: 0 });
            }
            this.scanMessage = `${product.name} added`;
            window.setTimeout(() => { this.scanMessage = ''; }, 1200);
            this.query = '';
            this.syncDefaultPayment();
        },

        handleSearchEnter() {
            const value = this.query.trim().toLowerCase();
            if (!value) return;
            const exact = this.catalog.find((product) =>
                product.sku.toLowerCase() === value
                || String(product.barcode || '').toLowerCase() === value
            );
            if (exact) this.addProduct(exact);
        },

        changeQuantity(line, delta) {
            const next = Math.max(1, Number(line.quantity) + delta);
            const stock = this.stockFor(line);
            if (line.track_inventory && next > stock) return;
            line.quantity = next;
            this.syncDefaultPayment();
        },

        removeLine(id) {
            this.cart = this.cart.filter((line) => line.id !== id);
            this.syncDefaultPayment();
        },

        clearCart() {
            this.cart = [];
            this.resetPayments();
        },

        get subtotal() {
            return this.cart.reduce((sum, line) => sum + (line.default_price_kobo * line.quantity), 0);
        },

        get discountTotal() {
            return this.cart.reduce((sum, line) => sum + Math.max(0, Number(line.discount || 0) * 100), 0);
        },

        get total() {
            return Math.max(0, this.subtotal - this.discountTotal);
        },

        get paidTotal() {
            return this.payments.reduce((sum, payment) => sum + Math.max(0, Number(payment.amount || 0) * 100), 0);
        },

        get balance() {
            return Math.max(0, this.total - this.paidTotal);
        },

        money(kobo) {
            return new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN', maximumFractionDigits: 2 }).format(kobo / 100);
        },

        addPayment() {
            this.payments.push({ payment_method_id: '', amount: '', reference: '' });
        },

        removePayment(index) {
            this.payments.splice(index, 1);
            if (!this.payments.length) this.addPayment();
        },

        resetPayments() {
            const preferred = this.paymentMethods.find((method) => method.is_default_for_pos) || this.paymentMethods[0];
            this.payments = [{ payment_method_id: preferred?.id || '', amount: '', reference: '' }];
        },

        syncDefaultPayment() {
            if (this.saleType === 'quote' || this.payments.length !== 1) return;
            this.payments[0].amount = (this.total / 100).toFixed(2);
        },

        switchType(type) {
            this.saleType = type;
            if (type === 'quote') {
                this.payments = [];
            } else if (!this.payments.length) {
                this.resetPayments();
                this.syncDefaultPayment();
            }
        },

        submit() {
            if (!this.branchId || !this.cart.length) return;
            this.submitting = true;
            this.$refs.form.submit();
        },
    }));
});
// EXPRESS CLOUD SPRINT 2 POS:END
