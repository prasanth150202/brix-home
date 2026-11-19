# BRIX Landing Page

A static landing page (HTML, CSS and JS) for the free "Scale your agency 30%" call.

It is live at **thebrix.io/brix-scale/**, served by the BRIX AI site from `public/brix-scale/`.
This folder keeps only the setup files, which are never served to visitors.

```
public/brix-scale/index.html      → page markup
public/brix-scale/styles.css      → all styling
public/brix-scale/script.js       → form steps, progress bar, Sheets submit, Calendly
public/brix-scale/assets/         → logo and favicon
public/brix-scale/.htaccess       → index file and short cache for this page
brix_scale/google-apps-script.gs  → paste into Google Apps Script (the Sheets backend)
```

## How the funnel works

1. A visitor clicks any CTA. A full-screen form opens with a progress bar.
2. The form has 5 questions, one at a time: full name, email, phone, agency name, and how many clients (a dropdown with an "Other" option that adds a text box).
3. On submit, the details are sent to Google Sheets as a new row with *Meeting Booked = No*.
4. The Calendly calendar opens with name and email already filled in.
5. When they book, that same row is updated to *Meeting Booked = Yes* with the time and the Calendly event link.

Leads who fill the form but don't book stay as **No** in the sheet. That's your follow-up list.

---

## Setup (about 10 minutes)

### 1. Google Sheets
1. Create a new Google Sheet, for example "BRIX Leads".
2. Go to **Extensions → Apps Script**.
3. Delete what's in `Code.gs` and paste in all of `google-apps-script.gs`.
   - Optional: set `NOTIFY_EMAIL` to get an email for each new lead.
4. Click **Deploy → New deployment**. Choose the gear icon, then **Web app**:
   - Execute as: **Me**
   - Who has access: **Anyone**
5. Click **Deploy**, authorize, and copy the **Web app URL** (it ends in `/exec`).
6. Open that URL in your browser. You should see "BRIX lead endpoint is live ✅".
7. In `public/brix-scale/script.js`, paste it into `CONFIG.SHEETS_ENDPOINT`.

The `Leads` tab and its headers are created automatically on the first submission.

> If you edit the Apps Script later, use **Deploy → Manage deployments → Edit → New version**.
> Otherwise the live URL keeps running the old code.

### 2. Calendly
1. Copy your event link, for example `https://calendly.com/brix/30-percent-call`.
2. Paste it into `CONFIG.CALENDLY_URL` in `public/brix-scale/script.js`.
3. Optional: prefill extra answers. In your Calendly event, under **Invitee questions**, add these in this order:
   1. *Agency name* (maps to `a1`)
   2. *How many clients do you handle?* (maps to `a2`)
   3. *Phone number* (maps to `a3`)

   To change the order, edit `CONFIG.CALENDLY_CUSTOM_ANSWERS`.

The lead ID is passed to Calendly as `utm_content`, so you can match any booking back to its sheet row.

### 3. CRM (optional)
Each lead can also be sent to your own CRM. The Apps Script forwards it from Google's servers, so your API key never appears on the website.

1. In the Apps Script, set `CRM_ENDPOINT` to your CRM's "create lead" URL.
2. Add the API key under **Project Settings** (gear icon) → **Script Properties** → **Add script property**.
   Name it `CRM_API_KEY` and paste the key as the value. Never put the key in the code or the website files.
3. Set `CRM_AUTH_HEADER` and `CRM_AUTH_PREFIX` to match how your CRM accepts the key. The default sends `Authorization: Bearer <key>`.
4. If your CRM expects different field names, rename the keys in `buildCrmPayload_()`.
5. Select `testCrm` in the editor's function dropdown and click **Run**. The result appears in the execution log.
6. Redeploy: **Manage deployments → Edit → New version**.

The CRM gets a `new_lead` request when the form is submitted, and a `booked` request when the meeting is booked.
Each row's **CRM Sync** column in the sheet shows whether the send worked (`OK 200` or `Failed …`).

### 4. Go live
Commit the changes in `public/brix-scale/` and deploy the BRIX AI site as usual. The page goes live at `thebrix.io/brix-scale/`, and `thebrix.io/brix-scale` redirects to it.

To test locally, run `serve.bat` and open http://localhost:8000/brix-scale/.

---

## Customising
- **Colours and fonts:** edit the variables at the top of `styles.css` (`--accent` is the brick orange).
- **Copy:** all text is in `index.html`.
- **Dropdown options:** edit the `<select id="clients">` block in `index.html`.
- **Tracking:** if Google Tag Manager is on the page, `brix_lead_submitted` and `brix_call_booked` are pushed to `dataLayer`.

## In-page booking (optional)

The BRIX Scale page can book calls itself, so visitors tap a time and never see Calendly's "Schedule Event" screen.
This is for the BRIX Scale page only; the main BRIX site and BRIX AI don't use it.

1. In Calendly, create a personal access token (Integrations → API and webhooks).
2. On the live site, open **Admin → BRIX Scale page → Calendly booking**, paste the token, pick the event type, switch it on and save.
   The token is stored in the site database on the server and is never sent to visitors. Don't put it in files or chat.

Until it's switched on, or whenever booking fails, the page shows the regular Calendly calendar instead.
Calendly's Free plan allows 5 API bookings a day; after that visitors get the calendar automatically.
Code: `app/controllers/brix_scale.php`, `app/BrixScale/`, `views/admin/brix_scale.php`, and the booking part of `public/brix-scale/script.js`.
