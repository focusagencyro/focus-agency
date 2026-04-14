# FOCUS AGENCY Redesign (Local)

Acest proiect este pregătit local în:

- `/Users/alexandruobreja/Desktop/FOCUS AGENCY`

## Ce este deja făcut

- Laravel + Blade + Tailwind + auth (login) + SQLite
- Import complet din sitemap-ul `https://www.focusagency.ro/sitemap.xml`
- `192` pagini importate cu text/conținut HTML
- URL logic păstrat prin fallback route
- Placeholdere AI pentru imagini (`.ai-placeholder`) în conținut

## Comenzi utile

```bash
cd "/Users/alexandruobreja/Desktop/FOCUS AGENCY"
composer run dev
```

Website local: `http://127.0.0.1:8000`

Sitemap preview local: `http://127.0.0.1:8000/sitemap-preview`

## Reimport conținut din sitemap

```bash
cd "/Users/alexandruobreja/Desktop/FOCUS AGENCY"
php scripts/import_focus_content.php
```

Output import:

- `storage/app/focus/pages.json`

## Build producție frontend

```bash
cd "/Users/alexandruobreja/Desktop/FOCUS AGENCY"
pnpm run build
```
