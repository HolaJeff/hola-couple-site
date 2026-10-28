# Hola Weddings V2

**Version:** 2.3.2
**Author:** Marco Cariolato — [mcweb.mx](https://mcweb.mx)
**Requires:** WordPress 5.8+, PHP 8.0+

A WordPress plugin that powers wedding couple sites built on the Hola Couples platform. It provides a multi-step booking form, quote management, room/package selection, insurance handling, payment processing, guest management, and a point-of-sale system — all integrated with the Classic Vacations API.

---

## Requirements

- The **Hola Weddings Library** plugin (`hola-weddings-library`) must be network-activated. This plugin will not load without it.
- Node.js 18+ and npm for front-end asset builds.
- PHP Composer for back-end dependencies (`vendor/`).

---

## Plugin Structure

```
hola-weddings-v2/
├── hola-weddings.php          # Plugin entry point
├── includes/                  # PHP classes (PSR-4, namespace HW\)
│   ├── App.php                # Main singleton — wires everything together
│   ├── Ajax.php               # WordPress AJAX handlers
│   ├── Scripts.php            # Asset enqueueing
│   ├── Quotes.php             # Booking form submission handler
│   ├── Routes.php             # Custom URL rewrites
│   ├── Api/
│   │   └── BookingEndpoints.php  # REST API endpoints (hw/v1/)
│   ├── Admin/                 # Admin pages and settings
│   ├── CPT/                   # Custom post types (Quote, Invoice, …)
│   ├── Controllers/           # POS and payment controllers
│   ├── Email/                 # Email templates and dispatch
│   ├── Models/                # Data models
│   └── Integrations/          # Classic Vacations API integration
├── template-parts/            # PHP template partials
│   └── booking-form/          # Legacy booking form templates
├── endpoints/                 # Page templates (book, home, travel-info)
├── templates/                 # Full-page templates (account, POS, confirmation)
├── assets/
│   ├── booking-form/          # React app source (see below)
│   ├── dist/                  # Compiled output — do not edit directly
│   │   ├── js/app.js          # Legacy compiled JS
│   │   └── booking-form/      # React compiled output
│   ├── js/                    # Legacy non-compiled JS (cart.js, account.js, …)
│   ├── src/js/                # Legacy Babel/Webpack source
│   └── style/                 # SCSS / CSS sources
├── webpack.config.cjs         # Webpack config for the React form (extends @wordpress/scripts)
├── gulpfile.babel.js          # Gulp build for legacy assets
├── package.json
└── composer.json
```

---

## Building Legacy Assets (Gulp)

These commands build the original JS and CSS assets used by all non-booking-form pages.

```bash
npm install

npm run dev          # watch + rebuild on change (Gulp default task)
npm run build-js     # compile JS only
npm run build-css    # compile CSS only
npm run build-pos    # compile the standalone POS module
```

---

## React Booking Form

The booking form is being progressively migrated to React. The React app lives in `assets/booking-form/` and is built separately from the legacy Gulp pipeline using plain **webpack 5 + Babel**.

The build is driven by [webpack.config.cjs](webpack.config.cjs). WordPress globals (`wp.element`, `wp.apiFetch`) are declared as `externals` so React and other WP packages are **not bundled** — they are loaded from WordPress core, keeping the output small.

### Toggling Between Legacy and React

There are 2 templates `Booking form ( Legacy )` and `Booking form ( New )`, assign them to the page as needed.

### Production Build

Compiles and minifies the React app to `assets/dist/booking-form/index.js`:

```bash
npm run build:form
```

### Development with HMR

Hot Module Replacement lets you edit React components and see changes instantly in the browser without a page reload.

**1. Add to your local `wp-config.php`** (never on production):

```php
define( 'HW_REACT_DEV', true );
```

**2. Start the dev server:**

```bash
npm run start:form
```

This starts webpack-dev-server on `http://localhost:8887`. The WordPress page continues to be served by your local server (XAMPP); only the JS is loaded from the dev server.

**3. Open the booking page in your browser.** Edits to any `.jsx` file in `assets/booking-form/` will hot-reload without a full page refresh.

> When `HW_REACT_DEV` is defined, `Scripts.php` automatically loads the script from `localhost:8887` instead of the compiled `dist/` file. Remove the constant (or set it to `false`) to switch back to the compiled build.

### React App Structure

```
assets/booking-form/
├── index.jsx                  # Entry point — mounts App into #hw-booking-form-root
├── App.jsx                    # Root component and step router
├── store/
│   └── bookingContext.jsx     # Global state (React Context + useReducer)
├── hooks/
│   ├── useBooking.js          # Step navigation helpers
│   ├── useCart.js             # Price calculation (totals, deposit)
│   └── useRooms.js            # Fetches room/package data from REST API
├── api/
│   └── booking.js             # wp.apiFetch() wrappers for hw/v1/ endpoints
└── components/
    ├── steps/
    │   ├── StepSearch.jsx     # Check-in/out dates + guest count
    │   ├── StepRooms.jsx      # Room or package selection
    │   ├── StepGuests.jsx     # Guest names and birthdates
    │   ├── StepBilling.jsx    # Customer contact information
    │   ├── StepInsurance.jsx  # Travel insurance (US guests only)
    │   └── StepReview.jsx     # Summary, T&C acceptance, submit
    └── cart/
        ├── CartSidebar.jsx    # Desktop sticky price summary
        └── MobileSummary.jsx  # Collapsible mobile cart drawer
```

### REST API Endpoints

The React app communicates with the plugin via `hw/v1/` REST routes registered in `includes/Api/BookingEndpoints.php`.

| Method | Endpoint         | Description                              |
|--------|------------------|------------------------------------------|
| GET    | `/hw/v1/rooms`   | Available rooms for given dates + guests |
| GET    | `/hw/v1/packages`| Available packages                       |
| POST   | `/hw/v1/quote`   | Submit a booking (replaces PHP form POST)|
| POST   | `/hw/v1/session` | Persist partial form data to session     |

---

## PHP Dependencies

```bash
composer install
```

---

## Logs

Runtime logs are written to `logs/`. The log level can be configured from the plugin's admin settings page.
