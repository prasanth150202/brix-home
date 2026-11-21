<?php
/** Privacy Policy. Static legal text, kept in code so it is versioned with the site. */
$email = 'support@thebrix.io';
$mail = '<a href="mailto:' . $email . '">' . $email . '</a>';
?>
<section class="page-hero">
  <div class="container narrow">
    <h1 class="page-title" data-split>Privacy Policy</h1>
    <p class="page-lead">Last updated: 21 November 2026</p>
  </div>
</section>
<div class="slab theme-light">
  <section class="page-body">
    <div class="container narrow">
      <div class="prose">
        <p>This Privacy Policy explains how BRIX collects, uses, shares and protects personal information when you visit our website, apply to our Partner Program, or use our Shopify apps.</p>

        <h2>Summary</h2>
        <ul>
          <li><strong>Who we are:</strong> BRIX is a product of Digifyce Global (OPC) Private Limited, a company registered in India.</li>
          <li><strong>What we collect:</strong> the details you give us (for example in our partner application), information about how you use our website, and the store data our Shopify apps need to work.</li>
          <li><strong>What we don't do:</strong> we don't sell your personal information, and we don't send you sales calls.</li>
          <li><strong>Your choices:</strong> you can ask us to access, correct or delete your data at any time by writing to <?= $mail ?>.</li>
        </ul>

        <h2>1. Who we are</h2>
        <p>BRIX ("BRIX", "we", "us" or "our") is operated by Digifyce Global (OPC) Private Limited, a company incorporated in India. You can reach us at <?= $mail ?>.</p>
        <p>For website visitors and Partner Program applicants, we are the data controller (the "data fiduciary" under Indian law). For data about a merchant's shoppers that our Shopify apps process, the merchant is the controller and we act as a processor on their behalf (see <a href="#merchant-data">Section 4</a>).</p>

        <h2>2. What this policy covers</h2>
        <p>This policy applies to:</p>
        <ul>
          <li>our website thebrix.io, including the BRIX Partner Program pages (such as <a href="<?= e(url('/brix-scale/')) ?>">thebrix.io/brix-scale/</a>);</li>
          <li>the BRIX Partner Program for agencies;</li>
          <li>our Shopify apps: Brix Convert, Brix Try On and Brix Connect (the "Apps").</li>
        </ul>
        <p>It doesn't cover the online stores of merchants who use our Apps. Each store has its own privacy policy.</p>

        <h2>3. Information we collect</h2>
        <h3>3.1 Information you give us</h3>
        <p>When you apply to the Partner Program or book a call:</p>
        <ul>
          <li>your agency's website and the number of Shopify stores you manage;</li>
          <li>your first name and work email address;</li>
          <li>your WhatsApp or mobile number, and your country code;</li>
          <li>the time you book for your call, and your time zone.</li>
        </ul>
        <p>When you join the Partner Program:</p>
        <ul>
          <li>the details needed to track your referrals and pay your commission, such as your business name, billing details and payout account details.</li>
        </ul>
        <p>When you contact us:</p>
        <ul>
          <li>the content of your emails, WhatsApp messages or support requests.</li>
        </ul>

        <h3>3.2 Information collected automatically on our website</h3>
        <p>When you visit our website, we and our service providers collect:</p>
        <ul>
          <li>device and browser information, such as browser type, operating system, screen size and language;</li>
          <li>IP address and the approximate location derived from it (country or city);</li>
          <li>usage information: pages viewed, time on page, scrolling, clicks, the steps you complete in our forms, and the page you came from;</li>
          <li>campaign information: UTM parameters and ad click identifiers (such as fbclid or gclid) in the link you used;</li>
          <li>cookies and similar technologies (see <a href="#cookies">Section 6</a>).</li>
        </ul>
        <p>We use these tools on our website:</p>
        <ul>
          <li><strong>Google Tag Manager</strong>, to manage the scripts below;</li>
          <li><strong>Google Analytics 4</strong>, to measure visits and how people use our pages;</li>
          <li><strong>Microsoft Clarity</strong>, for heatmaps and session recordings that show how visitors interact with our pages. Clarity masks text typed into form fields by default;</li>
          <li><strong>Meta Pixel and Meta Conversions API</strong>, to measure the results of our ads on Facebook and Instagram and show relevant ads. The Conversions API sends events from our server, together with technical data such as your IP address, browser user agent and Meta cookie identifiers.</li>
        </ul>

        <h3>3.3 Information processed through our Shopify Apps</h3>
        <p><strong>About merchants</strong> (the stores that install our Apps):</p>
        <ul>
          <li>store name and domain, the store owner's name and email, and the plan they use;</li>
          <li>billing for our Apps is handled by Shopify. We don't receive full card details.</li>
        </ul>
        <p><strong>Store data accessed through Shopify</strong>, only as each App needs it:</p>
        <ul>
          <li>products, collections, prices, discounts, and cart and order details. We use these to show upsells, bundles and offers, and to report results to the merchant.</li>
        </ul>
        <p><strong>About a merchant's shoppers</strong> (processed on the merchant's behalf):</p>
        <ul>
          <li><strong>Brix Try On:</strong> photos or images a shopper uploads or takes to use virtual try-on, and the try-on images we generate from them; email addresses and phone or WhatsApp numbers a shopper chooses to give the store; and the personalised email and WhatsApp campaigns sent from the App on the merchant's behalf;</li>
          <li><strong>Brix Convert:</strong> interactions with offers, such as offers seen, clicked or added to cart;</li>
          <li><strong>Brix Connect:</strong> browser push notification subscriptions (subscription tokens and browser details) and interactions with notifications.</li>
        </ul>

        <h2 id="merchant-data">4. Merchant and shopper data (our role as a processor)</h2>
        <p>When our Apps process information about a merchant's shoppers, we do so only on the merchant's instructions and to provide the App's features to that merchant. The merchant decides what to collect and is responsible for telling its shoppers and getting any consent required, for example consent before sending marketing emails or WhatsApp messages.</p>
        <p><strong>If you're a shopper:</strong> please contact the store you shopped with to access, correct or delete your data. We'll help that store respond.</p>
        <p>We support Shopify's privacy requests, including requests to provide or delete a shopper's data and to delete a store's data after it uninstalls our Apps, in line with Shopify's requirements.</p>
        <p><strong>Try On images:</strong> we use shopper photos only to create the try-on result for that shopper. We don't use them to identify anyone or to build facial recognition profiles. We keep them only as long as needed to provide the try-on feature to the merchant, and then delete them.</p>

        <h2>5. How we use information, and our legal bases</h2>
        <table>
          <thead>
            <tr><th>Purpose</th><th>Information used</th><th>Legal basis (EU/UK GDPR)</th></tr>
          </thead>
          <tbody>
            <tr><td>Reviewing your partner application, booking your call, sending your call link and reminders</td><td>Application and booking details</td><td>Steps you ask us to take before a contract, and our legitimate interests</td></tr>
            <tr><td>Running the Partner Program: tracking referrals and paying commission</td><td>Partner and referral details, payout details</td><td>Performance of our contract with you</td></tr>
            <tr><td>Providing our Apps and their features to merchants</td><td>Merchant data, store data, shopper data processed for the merchant</td><td>Performance of our contract with the merchant</td></tr>
            <tr><td>Customer support</td><td>Your messages and account details</td><td>Contract and legitimate interests</td></tr>
            <tr><td>Measuring and improving our website, forms and Apps</td><td>Usage and device information</td><td>Legitimate interests, or consent where the law requires it</td></tr>
            <tr><td>Measuring our ads and showing relevant ads on Facebook and Instagram</td><td>Pixel and Conversions API data, cookies</td><td>Consent where the law requires it, otherwise legitimate interests</td></tr>
            <tr><td>Security, fraud and spam prevention (including a hidden spam trap in our forms)</td><td>Technical and usage information</td><td>Legitimate interests</td></tr>
            <tr><td>Legal, tax and accounting obligations</td><td>Records of transactions and payouts</td><td>Legal obligation</td></tr>
          </tbody>
        </table>
        <p>Under India's Digital Personal Data Protection Act, 2023, we process personal data for the purposes you provide it for, or with your consent. You can withdraw consent at any time (see <a href="#your-rights">Section 10</a>).</p>
        <p><strong>We only contact you about what you asked for.</strong> We use the WhatsApp number and email you give us in the partner application to send your call confirmation, call link and reminders, and to follow up about the Partner Program. We don't make sales calls, and you can opt out at any time by replying "STOP" or emailing us.</p>

        <h2 id="cookies">6. Cookies and similar technologies</h2>
        <table>
          <thead>
            <tr><th>Tool</th><th>Provider</th><th>Purpose</th></tr>
          </thead>
          <tbody>
            <tr><td>Google Analytics 4</td><td>Google</td><td>Measures visits and how our website is used</td></tr>
            <tr><td>Google Tag Manager</td><td>Google</td><td>Loads and manages our website tags</td></tr>
            <tr><td>Microsoft Clarity</td><td>Microsoft</td><td>Heatmaps and session recordings to improve our pages</td></tr>
            <tr><td>Meta Pixel</td><td>Meta</td><td>Measures ad results and helps show relevant ads</td></tr>
            <tr><td>Form progress (local storage)</td><td>BRIX</td><td>Remembers your application progress so you don't have to start again</td></tr>
          </tbody>
        </table>
        <p><strong>How to control cookies:</strong></p>
        <ul>
          <li>block or delete cookies in your browser settings;</li>
          <li>opt out of Google Analytics with Google's browser add-on: <a href="https://tools.google.com/dlpage/gaoptout" target="_blank" rel="noopener">https://tools.google.com/dlpage/gaoptout</a>;</li>
          <li>manage ad preferences for Meta in your Facebook or Instagram account settings;</li>
          <li>where the law requires consent, we ask for it before setting non-essential cookies.</li>
        </ul>

        <h2>7. How we share information</h2>
        <p>We don't sell your personal information. We share it only with:</p>
        <ul>
          <li><strong>Service providers</strong> who help us run BRIX, under contracts that limit how they can use it:
            <ul>
              <li>Google (Google Workspace and Google Sheets for application records; Google Analytics; Google Tag Manager);</li>
              <li>Microsoft (Clarity);</li>
              <li>Meta Platforms (Meta Pixel and Conversions API);</li>
              <li>Cloudflare (website delivery, security and our server-side tracking);</li>
              <li>Shopify (App installation, data access and billing);</li>
              <li>scheduling providers, to book and manage partner calls;</li>
              <li>email and WhatsApp messaging providers, to send call confirmations, reminders and the campaigns merchants send through our Apps;</li>
              <li>AI and image-processing providers that power Brix Try On;</li>
              <li>payment and payout providers, for partner commissions.</li>
            </ul>
          </li>
          <li><strong>Merchants:</strong> data our Apps process for a store is available to that store.</li>
          <li><strong>Legal and safety:</strong> when the law requires it, or to protect our rights, users or the public.</li>
          <li><strong>Business transfers:</strong> if BRIX is involved in a merger, acquisition or sale of assets, under this policy's protections.</li>
        </ul>
        <p><strong>For US state privacy laws (including California):</strong> using the Meta Pixel and Conversions API to measure and target ads can count as "sharing" personal information for cross-context behavioral advertising. You can opt out by emailing <?= $mail ?> with the subject "Do Not Share". Where the law requires it, we also honor Global Privacy Control (GPC) browser signals.</p>

        <h2>8. International transfers</h2>
        <p>We're based in India, and our service providers process data in other countries, including the United States and the European Union. When we transfer personal data out of the EU, the UK or another country with transfer rules, we rely on appropriate safeguards, such as the European Commission's Standard Contractual Clauses, and we transfer data only as permitted by Indian law.</p>

        <h2>9. How long we keep information</h2>
        <p>We keep personal information only for as long as we need it for the purposes in this policy:</p>
        <ul>
          <li><strong>Partner applications:</strong> while we review your application and are in contact with you. If you don't join the Program, we delete or anonymise your application within 24 months of our last contact.</li>
          <li><strong>Partner records:</strong> for the length of your partnership and afterwards, as long as we need them for tax, accounting and legal reasons.</li>
          <li><strong>Website analytics:</strong> in line with each tool's retention settings (for example, Google Analytics keeps event data for up to 14 months).</li>
          <li><strong>Merchant and shopper data in our Apps:</strong> while the merchant uses the App. After uninstall, it's deleted in line with Shopify's requirements and the merchant's instructions.</li>
        </ul>

        <h2 id="your-rights">10. Your rights</h2>
        <p>Depending on where you live, you may have the right to:</p>
        <ul>
          <li>access the personal information we hold about you;</li>
          <li>correct inaccurate or incomplete information;</li>
          <li>delete your information;</li>
          <li>object to or restrict some processing, including processing based on legitimate interests;</li>
          <li>withdraw consent at any time, without affecting earlier processing;</li>
          <li>data portability: receive your data in a commonly used format;</li>
          <li>opt out of "sharing" or targeted advertising (US state laws), and not be discriminated against for using your rights;</li>
          <li>nominate another person to exercise your rights if you die or become unable to (India);</li>
          <li>complain to a data protection authority, such as your local EU authority, the UK ICO, or the Data Protection Board of India.</li>
        </ul>
        <p>To use any of these rights, email <?= $mail ?>. We may need to verify your identity first. We'll reply within 30 days, or sooner if the law requires.</p>
        <p>If you're a shopper at a store that uses our Apps, please contact that store first (see <a href="#merchant-data">Section 4</a>).</p>

        <h2>11. Security</h2>
        <p>We protect personal information with encrypted connections (HTTPS) and access controls that limit who can see your data. If a data breach affects you, we'll notify you and the relevant authorities as the law requires.</p>

        <h2>12. Children</h2>
        <p>Our website, Partner Program and Apps are for businesses and aren't directed at children. We don't knowingly collect personal information from anyone under 18. If you believe a child has given us personal information, contact us and we'll delete it.</p>

        <h2>13. Changes to this policy</h2>
        <p>We may update this policy from time to time. When we do, we'll change the "Last updated" date at the top. If the changes are significant, we'll let you know through our website or by email.</p>

        <h2>14. Contact us and grievances</h2>
        <p>For any privacy question, request or complaint, contact:</p>
        <p>
          Digifyce Global (OPC) Private Limited (BRIX)<br>
          Attention: Privacy / Grievance Officer<br>
          Email: <?= $mail ?>
        </p>
        <p>We'll acknowledge your request and resolve it within the time the law requires.</p>
      </div>
    </div>
  </section>
</div>
