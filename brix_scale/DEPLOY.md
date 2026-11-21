# Deploy: thebrix.io/brix-scale/ (landing page v2)

Upload into the live `/brix-scale/` folder (the folder that already has `index.html`, `styles.css`,
`script.js`, `assets/` and `vendor/`).

## 1. Back up first
Copy the current `index.html` and `script.js` to `index-old.html` and `script-old.js` (for rollback).

## 2. Upload these 4 files

| File in this zip | On the server | Action |
|---|---|---|
| `index.html` | `/brix-scale/index.html` | **Replace** |
| `script.js` | `/brix-scale/script.js` | **Replace** |
| `v2.css` | `/brix-scale/v2.css` | **Add** (new file) |
| `assets/partner-lead.jpg` | `/brix-scale/assets/partner-lead.jpg` | **Add** (new file) |

**Don't touch** `styles.css`, `vendor/`, the other files in `assets/`, `/brix-scale/thank-you/`,
or `/api/brix-scale/`. The new page still uses all of them.

## 3. Clear caches
Purge the LiteSpeed cache and the Cloudflare cache for `/brix-scale/*`.
Files are versioned (`v2.css?v=20261121-2`, `script.js?v=20261121-2`), so browsers pick up the new ones.

## 4. Check after deploy (2 minutes)
1. Open https://thebrix.io/brix-scale/ on a phone. The headline should read
   "Earn 30% recurring revenue from the Shopify clients you already manage."
2. The founder photo (Ragavendra Kumar) shows in the "Who you'll talk to" section.
3. Tap "Apply to become a partner". Step 1 asks for the agency website and the number of stores.
4. Browser console shows no errors. GTM `GTM-KKM9SQTC` loads.

## What changed (for reference)
- New page content and layout. `v2.css` loads after the existing `styles.css`.
- Form: same field names (`agencyName`, `fullName`, `email`, `phone`, `clients`), so the Google
  Sheet, the booking API and the tracking are unchanged. New step order: website + store count →
  first name + email → WhatsApp number.
- `script.js`: the website check on `agencyName`, a country-matched phone placeholder and error
  examples, and "first name" wording. Nothing else changed.

## Rollback
Restore `index-old.html` → `index.html` and `script-old.js` → `script.js`, then purge the cache.
