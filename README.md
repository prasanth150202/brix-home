# BRIX AI

The BRIX website with an AI chat on the homepage, and an admin panel to run it.

Visitors ask how to grow their Shopify average order value. BRIX AI answers from the case studies and product documents in the knowledge base (retrieval-augmented generation), shows the case studies it used, and recommends the BRIX app that fits, with an install button.

## Requirements

- PHP 8.1 or newer with `pdo_mysql`, `curl`, `mbstring`, `fileinfo`, `zip`, `dom` and `intl`
- MySQL 5.7+ or MariaDB 10.4+
- Composer

On this machine PHP 8.3 lives in `%LOCALAPPDATA%\Programs\php-8.3` so XAMPP (PHP 8.0) is untouched.

## Run it locally

1. Start MySQL (XAMPP Control Panel → MySQL → Start).
2. First time only: `"%LOCALAPPDATA%\Programs\php-8.3\php.exe" bin/install.php`
   - creates `config/config.php`, the database tables and the first admin account (printed once)
   - imports the case studies, product docs and blog posts from `C:/xampp/htdocs/thebrix_website` (`--import=<folder>` for another folder, `--no-import` to skip)
3. Double-click `serve.bat`, then open http://localhost:8000 and http://localhost:8000/admin.

Running Composer with PHP 8.3: `"%LOCALAPPDATA%\Programs\php-8.3\php.exe" C:\ProgramData\ComposerSetup\bin\composer.phar install`

## Turning on the AI

Admin → AI settings. Pick a provider and paste its key:

- **Claude (Anthropic)**, through the official PHP SDK. Default model Claude Opus 5.5 at low effort, which suits chat. The system prompt is cached between visitors, and Opus/Sonnet/Fable requests carry Anthropic's server-side refusal fallback.
- **ChatGPT (OpenAI)**, any model your OpenAI key can use. Default `gpt-5-mini`; GPT-5 and o-series models run at low reasoning effort.
- **OpenRouter**, any model on openrouter.ai, with a fallback model.

Without a key the chat runs in demo mode: it streams raw excerpts from the knowledge base so the whole flow can be tried. `ANTHROPIC_API_KEY` / `OPENAI_API_KEY` / `OPENROUTER_API_KEY` environment variables work too.

## How an answer is made

1. `POST /api/chat` checks the signed page token, the same-origin header and the per-visitor hourly limit.
2. Obvious prompt-injection attempts get the off-topic reply without calling the AI.
3. `KnowledgeBase::search()` finds the best passages with MySQL full-text search, expanded with the synonyms from AI settings (aov → average order value, …).
4. `PromptBuilder` sends the product catalog and case-study list (system prompt) plus the passages and question (user turn).
5. The reply streams back as server-sent events. The model ends with `[[brix:app=slug;sources=1,3]]`, which `MarkerFilter` strips and turns into the case-study and product cards.
6. Every question, answer, recommended product and install click is logged (Admin → Conversations).

Off-topic questions get the exact reply set in AI settings.

## Admin panel

| Area | What it does |
|---|---|
| Knowledge base | Upload PDF, Word, Markdown, text or HTML, or paste text. See and fix the extracted text and its passages. Case-study card fields (result, industry, link) and "feature on homepage". |
| Test retrieval | Type a question and see which passages BRIX AI would read. |
| Conversations | Every chat, with sources, recommendation, tokens and install clicks. |
| AI settings | Provider, keys, model, off-topic reply, extra instructions, synonyms, limits, connection test. |
| Homepage | Every heading and text block, stats, steps, feature tiles, show/hide per section. |
| Products, Pricing plans, Testimonials, FAQs, Question tags | Lists shown on the homepage. Products are what BRIX AI recommends. |
| Pages | Custom pages at any URL (`/about`, `/legal/privacy`), three layouts, custom CSS, SEO fields, header/footer menu. |
| Blog posts, Categories & tags | Rich-text editor, featured image, scheduling, SEO and share settings. |
| Media library | Image uploads used by the editor and image fields. |
| Site settings, Users | Logo, links, social, tracking code, admins and editors. |

Editors can do everything except AI settings, site settings and users.

### Sign-in email and password

- **My account** (top right when signed in) changes your own sign-in email and password. Both need your current password.
- Locked out? With server access, `php bin/reset-password.php` lists the accounts, and `php bin/reset-password.php --email=you@example.com [--password=...]` sets a new password (a random one is printed if you leave out `--password`).

## Deploying

1. Upload the project, run `composer install --no-dev`, and point the domain's document root at `public/`.
2. Create the database, copy `config/config.example.php` to `config/config.php`, fill it in, set `url` to the live address and `debug` to `false`, then run `php bin/install.php --no-import` (or import a database dump).
3. Apache uses `public/.htaccess`. On Nginx, send everything that isn't a file to `public/index.php` and turn off buffering for `/api/chat` (`proxy_buffering off` / `fastcgi_buffering off`) so answers stream.
4. Make `storage/` and `public/uploads/` writable by PHP.

## Layout

```
app/            PHP: helpers, routing, settings, auth, content queries
  Ai/           chat service, providers (Anthropic, OpenAI, OpenRouter, demo), prompt, marker filter
  Kb/           text extraction, chunking, retrieval
  controllers/  public site, chat API, admin
views/          site and admin templates
public/         front controller, CSS, JS (chat + particle dissolve), uploads
database/       schema.sql
bin/install.php setup and import
storage/        uploaded knowledge-base files and logs (not web-accessible)
```
