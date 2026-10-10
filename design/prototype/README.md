# Static design prototype (Phase 1)

Plain HTML/CSS/JS, with no build step and no dependencies. It is a design reference only, and **not** the production site.

```bash
cd design/prototype
python3 -m http.server 8080 --bind 0.0.0.0
# http://localhost:8080/index.html | admin.html | styleguide.html
```

- `assets/css/tokens.css`: design tokens (colors, type, spacing, motion), light and dark themes
- `assets/css/base.css`: reset, RTL/logical-property base, accessibility helpers, layout primitives
- `assets/css/components.css`: shared components (buttons, badges, cards, forms, alerts, table, empty state)
- `assets/css/site.css`: public-site layout and print rules
- `assets/css/admin.css`: admin-panel layout
- `assets/js/app.js`: theme toggle, mobile menu, confirm-on-submit (about 0.9 KB gzip)
- `assets/fonts/`: Vazirmatn variable v33.0.3 (SIL OFL 1.1, see `OFL.txt`)

Dashed orange **«یادداشت نمونه»** boxes are prototype-only notes and are not part of the design.
All content comes from the current live site or from user decisions (`docs/phase-0/decisions.md`). Nothing is invented.
Pages carry `noindex`.
