# Design System and Application Shell

## Direction

Express Cloud uses a light, calm, enterprise interface built from Zivora
navy, action blue, restrained teal, and semantic status colours.

The interface avoids gradients, glassmorphism, decorative clutter, filled
icon sets, and excessive card elevation.

## Shell dimensions

- Expanded sidebar: 280px
- Collapsed sidebar: 72px
- Top bar: 64px
- Desktop page padding: 24px
- Mobile page padding: 16px

The sidebar state persists in browser storage. Mobile uses a dedicated
slide-over navigation drawer.

## Navigation

The shell provides:

- grouped primary navigation;
- icon-only collapsed desktop state;
- tooltips through accessible titles when collapsed;
- mobile navigation drawer;
- global search field;
- notification entry;
- profile menu;
- visible logout action;
- Lisa AI as a first-class menu item.

Navigation visibility becomes permission-aware when authorization is
implemented.

## Interaction states

Reusable components provide:

- primary, secondary, ghost, and danger buttons;
- circular button loading indicator;
- disabled and busy states;
- skeleton loaders;
- toast notifications;
- modal dialogs;
- contextual drawers;
- status badges;
- accessible text inputs;
- top page-progress feedback.

Toasts appear top-right on desktop and at the bottom on mobile. Critical
messages may remain until dismissed.

## Accessibility

Components use:

- visible keyboard focus;
- semantic landmarks;
- minimum 44px controls;
- ARIA labels;
- escape-key dismissal where safe;
- reduced-motion support;
- high-contrast text and borders.

## Error pages

Custom foundations exist for:

- 401
- 403
- 404
- 419
- 429
- 500
- 503

Production errors expose no stack trace, SQL, filesystem path, secret, or
internal class name.

## Preview route

`/ui-preview` is available only in local and test environments. It is not
exposed in production.
