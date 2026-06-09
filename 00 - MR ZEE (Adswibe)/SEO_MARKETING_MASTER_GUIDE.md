# Adswibe® — SEO & Digital Marketing Master Guide
**Prepared for:** adswibe.com | **Date:** May 2026 | **Status:** Implementation Complete

---

## ✅ WHAT HAS ALREADY BEEN IMPLEMENTED IN YOUR CODE

The following changes were made directly to your website files:

### Technical SEO (Done in Code)
- ✅ Enhanced XML sitemap (`sitemap.xml`) — all 8 pages with proper priorities
- ✅ Image sitemap (`sitemap-images.xml`) — for Google Image Search indexing
- ✅ HTML sitemap page (`sitemap.html`) — user-navigable sitemap
- ✅ Upgraded `robots.txt` — blocks AI scrapers, allows all search bots, references both sitemaps
- ✅ Fixed duplicate `<link rel="canonical">` tags on clients.html and request.html
- ✅ `og:site_name` added to all pages
- ✅ `og:locale` added to all pages
- ✅ `twitter:site` and `twitter:creator` added to all pages

### Schema Markup (Done in Code)
- ✅ **Organization Schema** — enhanced with `foundingDate`, `numberOfEmployees`, `hasOfferCatalog`, `logo` as `ImageObject`
- ✅ **LocalBusiness Schema** — added with `geo`, `openingHoursSpecification`, `priceRange`, `aggregateRating`
- ✅ **WebSite Schema** — with `SearchAction` (Sitelinks Search Box eligible)
- ✅ **WebPage Schema** — with `breadcrumb` on homepage
- ✅ **FAQPage Schema** — expanded to 4 questions on homepage
- ✅ **AggregateRating Schema** — 4.9★ based on 185 reviews
- ✅ **Service Schema** — expanded with `hasOfferCatalog` listing 6 services on services.html
- ✅ **AboutPage Schema** — with breadcrumb on about.html
- ✅ **BreadcrumbList** — on homepage, about, services

### Tracking & Pixels (Placeholder Code Added — Requires Your IDs)
- ✅ **Google Tag Manager** — code on ALL pages (replace `GTM-W66NWM5C`)
- ✅ **Meta (Facebook) Pixel** — code on ALL pages (replace `964079973065268`)
- ✅ **TikTok Pixel** — code on ALL pages (replace `D82N0J3C77U77GE80K2G`)
- ✅ **Conversion Events** — `Lead`, `CompleteRegistration` fire on thank-you.html
- ✅ **Google Ads Conversion** — placeholder on thank-you.html (replace `AW-XXXXXXXXX`)

---

## 🔧 STEP 1: COMPLETE YOUR TRACKING IDs (Do This First)

Search for these placeholders in all HTML files and replace with real IDs:

| Placeholder | Replace With | Where to Get It |
|---|---|---|
| `GTM-W66NWM5C` | Your GTM Container ID | tagmanager.google.com |
| `964079973065268` | Your Meta Pixel ID | business.facebook.com → Events Manager |
| `D82N0J3C77U77GE80K2G` | Your TikTok Pixel ID | ads.tiktok.com → Assets → Events |
| `AW-XXXXXXXXX` | Your Google Ads ID | Google Ads → Tools → Conversions |
| `AW-XXXXXXXXX/YYYYYYYYYYYY` | Your conversion label | Google Ads → Conversions → Lead form submit |

---

## 🔧 STEP 2: GOOGLE TOOLS SETUP

### Google Search Console
1. Go to https://search.google.com/search-console
2. Add property → URL prefix → `https://adswibe.com`
3. Verify via HTML file upload OR DNS TXT record (recommended)
4. After verification → **Sitemaps** → Add `https://adswibe.com/sitemap.xml`
5. Add `https://adswibe.com/sitemap-images.xml`
6. Go to **URL Inspection** → submit each page for indexing:
   - `https://adswibe.com/`
   - `https://adswibe.com/services.html`
   - `https://adswibe.com/about.html`
   - `https://adswibe.com/clients.html`
   - `https://adswibe.com/tools.html`
   - `https://adswibe.com/request.html`

### Google Analytics 4 (GA4)
1. Go to https://analytics.google.com → Create Account → Create Property
2. Set up data stream for Web → enter `https://adswibe.com`
3. Copy the **Measurement ID** (format: `G-XXXXXXXXXX`)
4. In Google Tag Manager → add a new **Google Analytics: GA4 Configuration** tag
   - Measurement ID: your G-XXXXXXXXXX
   - Trigger: All Pages
5. Set up the following **Goals in GA4** (via GTM Events):
   - Form Submit → Trigger on form submit on request.html and contact pages
   - Phone Click → Trigger on `tel:` link clicks
   - WhatsApp Click → Trigger on WhatsApp button clicks
   - Page View: Thank You → thank-you.html pageview = conversion

### Google Tag Manager Setup
1. Go to https://tagmanager.google.com → Create Account → Container (Web)
2. Copy the Container ID (GTM-W66NWM5C) and replace all placeholders in your HTML
3. Add these **Tags** in GTM:
   - GA4 Configuration (trigger: All Pages)
   - GA4 Event: form_submit (trigger: form submission)
   - GA4 Event: phone_click (trigger: click on tel: links)
   - GA4 Event: whatsapp_click (trigger: click on wa.me links)
   - Meta Pixel: Lead (trigger: Thank You page view)
   - TikTok: CompleteRegistration (trigger: Thank You page view)
4. **Publish** the container

---

## 🔧 STEP 3: GOOGLE BUSINESS PROFILE

1. Go to https://business.google.com
2. Search for "Adswibe" — claim if exists, or create new
3. Business category: **Digital Marketing Agency** (primary), Social Media Consultant (secondary)
4. Complete ALL fields:
   - Business name: **Adswibe®**
   - Phone: +92 331 629 0097
   - Website: https://adswibe.com
   - Address: Lahore, Punjab, Pakistan
   - Hours: Mon–Fri 9 AM–6 PM
   - Description (750 chars): Use this: *"Adswibe® is a premium social media marketing agency based in Lahore, Pakistan. We specialize in Meta Ads (Facebook & Instagram), TikTok Ads, Google Ads, LinkedIn Marketing, and lead generation. With 250+ brands scaled globally and $20M+ in ad spend managed, we deliver measurable ROI. Serving clients in Pakistan, UAE, USA, UK, and worldwide. Results-driven. No long-term contracts."*
5. Add all your portfolio photos to the Photos section
6. Enable **Messaging** to receive direct messages
7. Set up **Products** → add each service as a product
8. Ask satisfied clients to leave Google Reviews (this is critical for Local SEO)

---

## 🔧 STEP 4: META ADS SETUP

### Account Structure
```
Meta Business Manager (business.facebook.com)
└── Ad Account: Adswibe® - [Client Name]
    ├── Campaign 1: Brand Awareness (CPM bidding)
    │   └── Ad Set: Pakistan 22-45, interests: business, ecommerce
    ├── Campaign 2: Traffic - Website (Link Click bidding)
    │   └── Ad Set: Lookalike 1% of past clients
    ├── Campaign 3: Lead Generation (Cost per Lead bidding)
    │   ├── Ad Set: Cold — Pakistan business owners
    │   └── Ad Set: Retargeting — website visitors 30 days
    └── Campaign 4: Retargeting (ROAS bidding)
        └── Ad Set: Engaged + Website Visitors 14 days
```

### Custom Audiences to Create
1. **Website Visitors — All** (180 days)
2. **Website Visitors — Proposal Page** (30 days) — hottest audience
3. **Video Viewers 75%** (60 days)
4. **Instagram Profile Engagers** (60 days)
5. **Facebook Page Engagers** (60 days)
6. **Customer List Upload** (your past/current clients' emails)

### Lookalike Audiences (create from above)
- 1% Lookalike of Customer List — Pakistan
- 1% Lookalike of Website Visitors — Pakistan + UAE
- 2-3% Lookalike of Proposal Page Visitors

### Ad Creative Recommendations
- **Hook (0–3 sec):** Show a dramatic BEFORE/AFTER result (ROAS 1.5x → 5x)
- **Body:** Specific numbers ($20M managed, 250+ brands)
- **CTA:** "Get a Free Audit" or "See Our Results"
- **Formats to test:** Reels (9:16), Square (1:1), Story (9:16)
- **Copy angles:** Social proof, urgency ("Limited spots"), problem-aware ("Wasting ad budget?")

### Budget Recommendation
- Start with **PKR 50,000–100,000/month** (~$180–350 USD)
- Allocate: 40% Lead Gen, 40% Retargeting, 20% Awareness
- Scale winning ad sets by 20% every 7 days

---

## 🔧 STEP 5: TIKTOK ADS SETUP

### Account Setup
1. Go to https://ads.tiktok.com → Create Business Account
2. Business name: Adswibe®, select Pakistan
3. Install TikTok Pixel → copy ID → replace `D82N0J3C77U77GE80K2G` in all pages
4. Set up **Web Events API** (server-side) for better tracking after iOS changes

### Campaign Structure
```
TikTok Ads Manager
├── Campaign 1: Awareness — Video Views
│   └── Ad Group: Pakistan, 22-40, business/marketing interests
├── Campaign 2: Traffic — Website Visits
│   └── Ad Group: Broad + Interest targeting
└── Campaign 3: Conversion — Leads
    └── Ad Group: Custom Audience (website visitors)
```

### Creative Best Practices for TikTok
- **Duration:** 15–30 seconds sweet spot
- **Format:** Always 9:16 vertical, minimum 720p
- **Hook:** First 2 seconds must stop the scroll — use text overlay + bold claim
- **Trending sounds:** Use TikTok's commercial sound library
- **Content ideas:**
  1. "How we scaled [brand] from 0 to PKR 1M/month with ads" (case study)
  2. "3 Meta Ads mistakes killing your ROAS" (educational)
  3. "Day in the life at a Lahore SMMA" (behind the scenes)
  4. "Reacting to bad ads vs good ads" (entertaining + educational)
- Use **Spark Ads** to boost your organic TikTok content (best ROI format)

---

## 🔧 STEP 6: GOOGLE ADS SETUP

### Account Structure
```
Google Ads Account
├── Campaign 1: Brand (Exact Match)
│   Keywords: [adswibe], [adswibe agency], [adswibe pakistan]
│   Bid: Manual CPC, protect brand at low cost
│
├── Campaign 2: Services — Core
│   Keywords: [meta ads agency pakistan], [smma lahore],
│   [facebook ads management pakistan], [tiktok ads agency lahore],
│   [social media marketing agency pakistan]
│   Bid: Target CPA after 30+ conversions
│
├── Campaign 3: Competitor
│   Keywords: [competitors smma], [alternative to X agency]
│   Bid: Manual CPC, aggressive
│
├── Campaign 4: Performance Max
│   Assets: Logo, headlines, descriptions, images, videos
│   Goal: Leads from all Google properties
│
└── Campaign 5: Display Retargeting
    Audience: Website Visitors (all pages)
    Bid: Target CPA
```

### Target Keywords (Prioritized)
**High Intent (run these first):**
- `smma lahore` — moderate volume, high intent
- `social media marketing agency pakistan` — high volume
- `meta ads agency pakistan` — commercial intent
- `facebook ads management lahore` — local intent
- `google ads agency lahore` — service intent
- `tiktok ads agency pakistan` — growing volume

**Long-tail (lower CPC, high conversion):**
- `best smma in pakistan`
- `social media marketing agency for ecommerce pakistan`
- `hire facebook ads expert pakistan`
- `results based smma pakistan`

### Negative Keywords (Add Immediately)
`free, jobs, internship, salary, course, learn, tutorial, DIY, reddit, youtube, fiverr, upwork, freelancer, cheap, affordable, $5, 500rs, template, how to, what is`

### Ad Extensions to Set Up
1. **Sitelinks:** Services, Client Results, Free Audit, About Us
2. **Callout:** "250+ Brands Scaled", "$20M+ Ad Spend Managed", "No Long-term Contracts"
3. **Call Extension:** +92 331 629 0097
4. **Location Extension:** Link to Google Business Profile
5. **Structured Snippets:** Services → Meta Ads, TikTok Ads, Google Ads, LinkedIn
6. **Lead Form Extension:** For mobile — capture leads without leaving Google

### Budgets
- Brand Campaign: PKR 3,000/day
- Services Campaign: PKR 8,000–15,000/day
- Performance Max: PKR 10,000/day
- Display Retargeting: PKR 3,000/day

---

## 🔧 STEP 7: ON-PAGE KEYWORD TARGETS (Already in Site)

| Page | Primary Keyword | Secondary Keywords |
|---|---|---|
| index.html | social media marketing agency pakistan | smma lahore, best smma 2026, meta ads agency |
| services.html | social media marketing services | meta ads, tiktok ads, google ads management |
| about.html | smma lahore | digital marketing agency pakistan |
| clients.html | smma case studies | social media agency results pakistan |
| tools.html | free marketing tools | social media tools online |
| request.html | hire social media agency | smma proposal pakistan |

---

## 🔧 STEP 8: OFF-PAGE SEO — BACKLINK STRATEGY

### Immediate Actions (Week 1–2)
1. **Google Business Profile** — verified listing (counts as a citation)
2. **Submit to Pakistani Business Directories:**
   - pakbiz.com
   - businesslist.pk
   - yellowpages.com.pk
   - rozee.pk (employer profile)
   - graana.com business listings
3. **Submit to Global Directories:**
   - clutch.co (most important — rank on "best smma pakistan")
   - goodfirms.co
   - designrush.com
   - upcity.com
   - sortlist.com

### Month 1–3 Strategy
4. **Guest Posts** — target these types of sites:
   - Pakistani marketing/business blogs
   - Startup Pakistan, TechJuice, ProPakistani (pitch how-to articles)
   - LinkedIn articles (publish 2/month, link back to site)
5. **HARO (Help a Reporter Out):** Sign up at helpareporter.com — respond to marketing/business queries, get cited by journalists
6. **Case Study Submissions:** Submit your best results to marketing communities

### Target Anchor Text Distribution
- Branded (60%): "Adswibe", "Adswibe®", "adswibe.com"
- Partial match (25%): "social media agency pakistan", "smma lahore"
- Generic (15%): "click here", "learn more", "this agency"

---

## 🔧 STEP 9: CONTENT MARKETING STRATEGY

### Blog Topic Ideas (Keyword-Focused)
Publish these as blog posts to drive organic traffic:

1. "Meta Ads vs TikTok Ads: Which is Better for Pakistani Brands in 2026?"
2. "How to Calculate ROAS and What's a Good ROAS in Pakistan"
3. "Complete Guide to Facebook Ads for E-commerce in Pakistan"
4. "TikTok Ads Pakistan: A Beginner's Complete Guide 2026"
5. "Why Your Meta Ads Are Not Converting (And How to Fix It)"
6. "Google Ads vs Meta Ads: What Should Pakistani Businesses Choose"
7. "Social Media Marketing Pricing in Pakistan: What to Expect"
8. "How We Scaled a Lahore Car Detailing Brand to 200+ Leads/Month"

### Content Calendar (Monthly)
- Week 1: Publish 1 SEO blog post (1,500+ words)
- Week 2: 1 TikTok video (educational/case study)
- Week 3: 1 LinkedIn article + share on all social
- Week 4: 1 client case study or results post

### Pillar Page Strategy
Create a comprehensive "Meta Ads Guide for Pakistan" page (3,000+ words) and link all related shorter posts to it. This builds topical authority.

---

## 🔧 STEP 10: PERFORMANCE MONITORING

### Weekly Checks
- [ ] Google Search Console → Performance → check impressions/clicks/CTR
- [ ] GA4 → check sessions, conversions, traffic sources
- [ ] Check keyword rankings (use free tool: Google Search Console)

### Monthly SEO Audit
- [ ] Run Google PageSpeed Insights on all pages → target 90+
- [ ] Check for broken links (use Screaming Frog or Sitechecker.pro)
- [ ] Review new backlinks in Google Search Console → Links
- [ ] Update blog content older than 6 months
- [ ] Check competitors' new content and keywords

### KPIs to Track
| Metric | Target |
|---|---|
| Organic Sessions | +30% QoQ |
| Keyword Rankings (Top 10) | 10+ target keywords |
| Domain Authority | 20+ (new site) |
| Conversion Rate | 3–5% (proposal requests) |
| Google Business Impressions | 500+/month |
| Backlinks | +10 quality links/month |

---

## 🔧 STEP 11: TECHNICAL SEO CHECKLIST (Remaining Actions)

### Server/Hosting Level (Do with Your Host)
- [ ] Enable **GZIP/Brotli compression** in .htaccess or server config
- [ ] Set up **browser caching** (cache CSS/JS/images for 1 year)
- [ ] Force **HTTPS** (301 redirect HTTP → HTTPS)
- [ ] Redirect `www` → non-www (or vice versa) — be consistent
- [ ] Set up **CDN** (Cloudflare free tier is excellent — also adds security)

### Cloudflare (Strongly Recommended — Free)
1. Sign up at cloudflare.com
2. Add your domain
3. Enable: **Auto Minify** (JS, CSS, HTML), **Brotli compression**, **Rocket Loader**
4. Set **Browser Cache TTL**: 1 year
5. Enable **HTTP/2** and **HTTP/3**
6. Under **Page Rules**: Cache static assets (images, CSS, JS)

### Image Optimization (Your images are already WebP — great!)
- [ ] Compress any remaining PNGs in `/images/abv/` (some are 1–10MB)
- [ ] Add `loading="lazy"` to all below-fold images
- [ ] Ensure all `<img>` tags have descriptive `alt` attributes

---

## 📋 QUICK WIN CHECKLIST (Do in Next 7 Days)

- [ ] Replace all `GTM-W66NWM5C` with real GTM ID
- [ ] Replace all `964079973065268` with real Meta Pixel ID
- [ ] Replace all `D82N0J3C77U77GE80K2G` with TikTok Pixel ID
- [ ] Set up Google Search Console and submit sitemaps
- [ ] Set up/claim Google Business Profile
- [ ] Set up Cloudflare (free CDN + performance boost)
- [ ] Create Clutch.co profile (critical for SMMA SEO)
- [ ] Validate all schemas at: https://search.google.com/test/rich-results
- [ ] Test site on PageSpeed Insights: https://pagespeed.web.dev

---

*This guide was generated specifically for Adswibe® (adswibe.com) based on analysis of your website codebase.*
