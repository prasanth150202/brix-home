/**
 * BRIX — Google Sheets lead collector
 * ------------------------------------------------------------
 * Paste this into: Google Sheet → Extensions → Apps Script → Code.gs
 * Then: Deploy → New deployment → Web app
 *        Execute as: Me   |   Who has access: Anyone
 * Copy the Web app URL (ends in /exec) into CONFIG.SHEETS_ENDPOINT in script.js.
 *
 * Handles two actions sent by the website:
 *   action=lead    → appends a new row
 *   action=booked  → marks that lead's row as "Meeting booked = Yes"
 */

const SHEET_NAME = 'Leads';

// Optional: get an email for every new lead. Leave '' to turn off.
const NOTIFY_EMAIL = '';

/**
 * Optional: forward every lead to your CRM. Leave CRM_ENDPOINT as '' to turn off.
 * The call is made from Google's servers, so your API key is never exposed on the website.
 * Adjust buildCrmPayload_() below to match the field names your CRM expects.
 */
const CRM_ENDPOINT = '';          // e.g. 'https://your-crm.com/api/leads'

// The API key is NOT written here. Add it in Apps Script → Project Settings (gear icon)
// → Script Properties → Add property:  CRM_API_KEY = <your key>
// Then set how your CRM expects to receive it:
const CRM_AUTH_HEADER = 'Authorization';  // some CRMs use 'x-api-key' or 'api-key'
const CRM_AUTH_PREFIX = 'Bearer ';        // use '' if the CRM wants the raw key

function crmHeaders_() {
  const key = PropertiesService.getScriptProperties().getProperty('CRM_API_KEY');
  return key ? { [CRM_AUTH_HEADER]: CRM_AUTH_PREFIX + key } : {};
}

const HEADERS = [
  'Timestamp',
  'Lead ID',
  'Full Name',
  'Email',
  'Phone',
  'Agency Name',
  'Clients Handling',
  'Clients (Other)',
  'Meeting Booked',
  'Booked At',
  'Calendly Event',
  'UTM Source',
  'UTM Medium',
  'UTM Campaign',
  'UTM Content',
  'UTM Term',
  'Page URL',
  'Referrer',
  'User Agent',
  'CRM Sync',
];

const COL = Object.fromEntries(HEADERS.map((h, i) => [h, i + 1]));

function doPost(e) {
  const lock = LockService.getScriptLock();
  lock.waitLock(15000);
  try {
    const p = (e && e.parameter) || {};

    // Honeypot: bots fill hidden fields — silently ignore them
    if (p.website) return json_({ ok: true });

    const sheet = getSheet_();

    if (p.action === 'booked') {
      const bookedRow = markBooked_(sheet, p);
      if (bookedRow) {
        sheet.getRange(bookedRow, COL['CRM Sync']).setValue(
          sendToCrm_(buildCrmPayload_(readRow_(sheet, bookedRow), 'booked'))
        );
      }
      return json_({ ok: true, action: 'booked' });
    }

    // Basic server-side checks
    if (!p.fullName || !p.email) return json_({ ok: false, error: 'Missing required fields' });

    const row = new Array(HEADERS.length).fill('');
    row[COL['Timestamp'] - 1] = new Date();
    row[COL['Lead ID'] - 1] = clean_(p.leadId);
    row[COL['Full Name'] - 1] = clean_(p.fullName);
    row[COL['Email'] - 1] = clean_(p.email);
    row[COL['Phone'] - 1] = clean_(p.phone);
    row[COL['Agency Name'] - 1] = clean_(p.agencyName);
    row[COL['Clients Handling'] - 1] = clean_(p.clients);
    row[COL['Clients (Other)'] - 1] = clean_(p.clientsOther);
    row[COL['Meeting Booked'] - 1] = 'No';
    row[COL['UTM Source'] - 1] = clean_(p.utm_source);
    row[COL['UTM Medium'] - 1] = clean_(p.utm_medium);
    row[COL['UTM Campaign'] - 1] = clean_(p.utm_campaign);
    row[COL['UTM Content'] - 1] = clean_(p.utm_content);
    row[COL['UTM Term'] - 1] = clean_(p.utm_term);
    row[COL['Page URL'] - 1] = clean_(p.pageUrl);
    row[COL['Referrer'] - 1] = clean_(p.referrer);
    row[COL['User Agent'] - 1] = clean_(p.userAgent);

    sheet.appendRow(row);
    const newRow = sheet.getLastRow();
    sheet.getRange(newRow, COL['CRM Sync']).setValue(
      sendToCrm_(buildCrmPayload_(readRow_(sheet, newRow), 'new_lead'))
    );

    if (NOTIFY_EMAIL) {
      MailApp.sendEmail({
        to: NOTIFY_EMAIL,
        subject: 'New BRIX lead: ' + clean_(p.fullName) + ' (' + clean_(p.agencyName) + ')',
        body:
          'Name: ' + p.fullName + '\n' +
          'Email: ' + p.email + '\n' +
          'Phone: ' + p.phone + '\n' +
          'Agency: ' + p.agencyName + '\n' +
          'Clients: ' + p.clients + (p.clientsOther ? ' — ' + p.clientsOther : '') + '\n\n' +
          'Open sheet: ' + SpreadsheetApp.getActiveSpreadsheet().getUrl(),
      });
    }

    return json_({ ok: true, action: 'lead' });
  } catch (err) {
    console.error(err);
    return json_({ ok: false, error: String(err) });
  } finally {
    lock.releaseLock();
  }
}

// Visiting the URL in a browser shows this — handy to confirm the deployment works
function doGet() {
  return ContentService.createTextOutput('BRIX lead endpoint is live ✅');
}

/* ---------- helpers ---------- */

function getSheet_() {
  const ss = SpreadsheetApp.getActiveSpreadsheet();
  let sheet = ss.getSheetByName(SHEET_NAME);
  if (!sheet) sheet = ss.insertSheet(SHEET_NAME);
  if (sheet.getLastRow() === 0) {
    sheet.appendRow(HEADERS);
    sheet.getRange(1, 1, 1, HEADERS.length).setFontWeight('bold').setBackground('#0a0a0a').setFontColor('#ffffff');
    sheet.setFrozenRows(1);
  }
  return sheet;
}

function markBooked_(sheet, p) {
  const lastRow = sheet.getLastRow();
  if (lastRow < 2) return;

  let rowIndex = -1;

  // 1) Match by Lead ID
  if (p.leadId) {
    const hit = sheet.getRange(2, COL['Lead ID'], lastRow - 1, 1)
      .createTextFinder(String(p.leadId)).matchEntireCell(true).findNext();
    if (hit) rowIndex = hit.getRow();
  }
  // 2) Fallback: latest row with the same email
  if (rowIndex === -1 && p.email) {
    const emails = sheet.getRange(2, COL['Email'], lastRow - 1, 1).getValues();
    for (let i = emails.length - 1; i >= 0; i--) {
      if (String(emails[i][0]).toLowerCase() === String(p.email).toLowerCase()) { rowIndex = i + 2; break; }
    }
  }
  if (rowIndex === -1) return null;

  sheet.getRange(rowIndex, COL['Meeting Booked']).setValue('Yes');
  sheet.getRange(rowIndex, COL['Booked At']).setValue(new Date());
  sheet.getRange(rowIndex, COL['Calendly Event']).setValue(clean_(p.eventUri));
  return rowIndex;
}

/* ---------- CRM ---------- */

function readRow_(sheet, rowIndex) {
  const values = sheet.getRange(rowIndex, 1, 1, HEADERS.length).getValues()[0];
  return Object.fromEntries(HEADERS.map((h, i) => [h, values[i]]));
}

// Map sheet columns → the JSON your CRM expects. Rename keys to match your CRM's API.
function buildCrmPayload_(r, event) {
  const strip = (v) => String(v || '').replace(/^'/, ''); // undo the formula-guard apostrophe
  return {
    event: event,                       // 'new_lead' or 'booked'
    lead_id: strip(r['Lead ID']),
    full_name: strip(r['Full Name']),
    email: strip(r['Email']),
    phone: strip(r['Phone']),
    agency_name: strip(r['Agency Name']),
    clients: strip(r['Clients Handling']),
    clients_other: strip(r['Clients (Other)']),
    meeting_booked: r['Meeting Booked'] === 'Yes',
    calendly_event: strip(r['Calendly Event']),
    source: 'BRIX landing page',
    utm_source: strip(r['UTM Source']),
    utm_medium: strip(r['UTM Medium']),
    utm_campaign: strip(r['UTM Campaign']),
    created_at: r['Timestamp'] instanceof Date ? r['Timestamp'].toISOString() : '',
  };
}

function sendToCrm_(payload) {
  if (!CRM_ENDPOINT) return '';
  try {
    const res = UrlFetchApp.fetch(CRM_ENDPOINT, {
      method: 'post',
      contentType: 'application/json',
      headers: crmHeaders_(),
      payload: JSON.stringify(payload),
      muteHttpExceptions: true,
    });
    const code = res.getResponseCode();
    if (code >= 200 && code < 300) return 'OK ' + code + ' (' + payload.event + ')';
    console.error('CRM error', code, res.getContentText());
    return 'Failed ' + code + ' (' + payload.event + ')';
  } catch (err) {
    console.error('CRM error', err);
    return 'Failed: ' + String(err).slice(0, 200);
  }
}

// Run this once from the Apps Script editor to test your CRM connection.
function testCrm() {
  const result = sendToCrm_(buildCrmPayload_({
    'Lead ID': 'test-123', 'Full Name': 'Test Lead', 'Email': 'test@example.com',
    'Phone': '+10000000000', 'Agency Name': 'Test Agency', 'Clients Handling': '1-5',
    'Timestamp': new Date(),
  }, 'new_lead'));
  console.log(result || 'CRM_ENDPOINT is empty');
}

// Stops values like "+91 98..." or "=HYPERLINK(...)" from being treated as formulas
function clean_(v) {
  if (v === undefined || v === null) return '';
  let s = String(v).trim().slice(0, 1000);
  if (/^[=+\-@]/.test(s)) s = "'" + s;
  return s;
}

function json_(obj) {
  return ContentService.createTextOutput(JSON.stringify(obj)).setMimeType(ContentService.MimeType.JSON);
}
