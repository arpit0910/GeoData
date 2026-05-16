# SetuGeo — Detailed Sales & Product Report
### Comprehensive Business Intelligence Document
**Date:** May 2026 | **Classification:** Internal + Prospect-Facing

---

## EXECUTIVE SUMMARY

**SetuGeo** is a SaaS API platform providing structured, reliable data infrastructure for developers and businesses operating in India's digital economy. The platform aggregates and serves geospatial data, Indian banking infrastructure, financial market intelligence (equities, mutual funds, indices), and document OCR — all through a single, credit-based REST API.

**The core value proposition:** Instead of negotiating data licenses from NSE/BSE, scraping government portals for pincode data, building IFSC validation from scratch, and hiring a team to do manual KYC verification — customers pay a monthly subscription and get all of it through one integration.

**Market Opportunity:**
- India has 7,500+ active fintech startups
- 150 million+ demat account holders (growing at 40% YoY)
- $200 billion e-commerce market requiring address infrastructure
- 100,000+ bank branches requiring digital discovery
- Regulatory push for digital KYC across all financial services

---

## SECTION 1: PRODUCT BREAKDOWN

### 1.1 Geospatial Data APIs

**What it is:**
A comprehensive global and India-specific location data service providing geographic hierarchies, postal data, proximity tools, and geospatial calculations.

**Data Coverage:**

| Entity | Volume | Details |
|---|---|---|
| Countries | 190+ | Name, ISO codes, capital, region, GDP, income level, tax info, UN membership |
| States / UTs | All India + Global | Linked to countries, hierarchical |
| Cities | Comprehensive India + Major Global | Population, coordinates |
| Pincodes | Full India | Lat/lng coordinates, city, state, district linkage |
| Timezones | Global | UTC offsets, DST support |
| Regions | UN Classification | 5 regions, 22 sub-regions |

**Key API Capabilities:**
- Country detail & comparison (economic profiles, tax rates, development indicators)
- State → City → Pincode hierarchical drill-down
- Proximity search: "Find all pincodes within X km of this lat/lng"
- Distance calculation between two coordinates (Haversine formula — accurate for GPS distances)
- Geospatial clustering: group locations into delivery zones or service areas
- Address validation and autocomplete
- Timezone detection from coordinates
- Currency information per country

**Who Buys This:**

- **E-Commerce & Logistics:** Checkout address validation, delivery zone creation, nearest warehouse lookup, serviceable pincode checking
- **Real Estate / PropTech:** Location search, proximity to amenities, jurisdiction classification
- **HRMS / Recruitment:** Validating candidate addresses, jurisdiction-based payroll rules
- **Insurance:** Geo-risk assessment, coverage area mapping
- **Healthcare:** Patient location mapping, nearest facility search

**Competitive Advantage:**
Most solutions provide either global country data OR India-specific pincode data — not both. SetuGeo provides both with consistent API design, including economic metadata (GDP, income level, OECD status) that competitors don't include.

---

### 1.2 Banking Infrastructure APIs

**What it is:**
A searchable directory of all Indian banks and their branch networks, including IFSC codes, addresses, and digital banking availability data.

**Data Coverage:**

| Entity | Details |
|---|---|
| Banks | All scheduled Indian banks — public, private, cooperative, RRBs |
| Bank Branches | Branch name, address, city, state, IFSC code, SWIFT code |
| Search Dimensions | By bank, by IFSC, by city, by state |
| Coverage Analysis | Banking availability by geography |

**Key API Capabilities:**
- Validate an IFSC code (critical for NEFT/RTGS/IMPS payment rails)
- Find all branches of a given bank
- Find all banks operating in a city or state
- Coverage gap analysis: identify areas with limited banking access
- Digital banking availability mapping

**Who Buys This:**

- **Fintech / Payments:** IFSC validation at payment initiation (prevents failed transfers)
- **Lending / NBFC:** Verify branch existence for KYC, disbursement routing
- **Insurance:** Agent territory mapping, branch-level policy distribution
- **Banks themselves:** Competitive intelligence, coverage benchmarking
- **Government / CSR:** Financial inclusion analysis — identifying underbanked geographies

**Business Impact:**
A single failed payment due to an incorrect IFSC costs 10x–50x more in remediation (refunds, support tickets, customer trust) than the API subscription cost. For any app moving money in India, IFSC validation is non-negotiable.

---

### 1.3 Indian Financial Market Intelligence APIs

This is SetuGeo's most differentiated and high-value module. It provides structured, API-accessible market data for Indian equities, mutual funds, and indices — without the complexity of direct exchange data feeds.

#### 1.3.1 Equity (Stock Market) APIs

**Data Coverage:**
- 5,000+ listed companies across NSE and BSE
- Daily OHLC (Open, High, Low, Close) price data
- Fundamental metadata: market cap, sector, industry, CIN, registered office
- Dual-exchange data: NSE vs BSE spread analysis

**Key API Capabilities:**

| API | Description |
|---|---|
| Equity List & Search | Filter by name, symbol, sector, exchange |
| Daily Price Data | OHLC per equity per exchange |
| Top Gainers/Losers | Ranked by % change (daily/weekly) |
| 52-Week High/Low | Stocks at/near annual extremes |
| High Volume | Volume spike detection |
| Intraday Movers | Stocks with unusual intraday movement |
| Gap Movers | Stocks that opened significantly up/down |
| Consistent Performers | Stocks with sustained directional movement |
| New Listings | Recently IPO'd or listed companies |
| Sector Heatmap | Performance visualization by sector |
| Peer Comparison | Compare fundamentals across similar stocks |
| Equity History | Historical price series for charts |
| Dual Exchange Spread | NSE vs BSE price difference |

**Who Buys This:**

- **Investment / Trading Apps:** Real-time data display, charting, portfolio valuation
- **Wealth Management Platforms:** Client portfolio dashboards, risk analytics
- **Financial Research Tools:** Screener-style filtering and analysis
- **Robo-advisors:** Automated portfolio rebalancing based on market signals
- **News & Media Fintech:** Market data widgets, ticker displays
- **Corporate Finance Teams:** Competitor stock monitoring

**Why This Matters for Sales:**
Getting NSE/BSE data directly requires an exchange membership or a SEBI-registered data vendor agreement. Both involve significant fees, compliance overhead, and technical complexity. SetuGeo removes all of that — a developer can have production market data flowing to their app within hours of signing up.

#### 1.3.2 Mutual Fund APIs

**Data Coverage:**
- 5,000+ mutual fund schemes across all AMCs (Asset Management Companies)
- Daily NAV (Net Asset Value) history
- Performance metrics: 1-day, 3-day, 7-day, 1-month, 3-month, 6-month, 1-year, 3-year returns

**Key API Capabilities:**

| API | Description |
|---|---|
| Fund List & Search | By name, AMC, category, sub-category |
| NAV History | Historical NAV series for charting |
| Performance Rankings | Ranked by return period (1d to 3y) |
| Category Returns | Average return by fund category |
| AMC Performance | Aggregate performance by fund house |
| Consistent Performers | Funds with sustained positive returns |
| Similar Funds | Recommendation engine for comparable funds |
| Fund Comparison | Side-by-side return comparison |

**Who Buys This:**
- Mutual fund distribution platforms (MFDs, RIAs)
- Investment apps offering SIP management
- Wealth management tools for HNI advisors
- Corporate treasury management tools
- Financial comparison websites

#### 1.3.3 Market Index APIs

**Data Coverage:**
- 25+ NSE & BSE indices (NIFTY 50, NIFTY BANK, NIFTY IT, SENSEX, etc.)
- Daily OHLC for each index
- Valuation metrics (P/E, P/B ratios)

**Key API Capabilities:**
- Live index levels and daily change %
- Historical index series
- Top gainers/losers among index constituents
- Cross-index comparison and valuation benchmarking
- Market breadth (advances vs declines)

#### 1.3.4 Market Snapshot API

A single API call returning a consolidated view of Indian market health:
- All major indices with current values
- Market breadth (number of advancing vs declining stocks)
- Sector performance summary
- Top equity movers
- Sentiment indicators

**Sales Angle:** Perfect for fintech dashboards, app home screens, and daily market briefing notifications — one API call replaces 5–10 individual calls.

---

### 1.4 Document OCR APIs

**What it is:**
An AI-powered document recognition service that extracts structured data from Indian identity and financial documents — enabling automated KYC workflows.

**Supported Document Types:**
- PAN Card
- Aadhaar Card (Front)
- Aadhaar Card (Back)
- Voter ID (Election Commission card)
- Driving License
- Passport

**Supported Image Formats:** JPEG, PNG, WebP, BMP, TIFF

**Output:**
- Auto-detected document type
- Extracted fields as structured JSON (name, DOB, ID number, address, etc.)
- Confidence score per extracted field

**Technical Architecture:**
- Powered by Tesseract OCR (battle-tested, production-grade)
- Runs as a separate FastAPI (Python) microservice
- Accessed through SetuGeo's unified API layer
- No document storage — processed and discarded for privacy

**Who Buys This:**

| Buyer | Use Case |
|---|---|
| Lending / NBFC | Automated KYC for loan applications |
| Insurance | Policy issuance KYC compliance |
| Broking & Wealth | Demat account onboarding |
| HR / Staffing | Employee document verification |
| Telecom | SIM card activation KYC |
| Real Estate | Tenant/buyer verification |

**Business Impact Calculation:**
- Average KYC team processes 100–200 documents/day manually
- Cost: ₹150–200/hour per operator × 8 hours = ₹1,200–1,600/day
- SetuGeo OCR processes the same volume in minutes at a fraction of the cost
- Payback period: typically under 30 days for any team doing >50 KYC/day

---

### 1.5 Utility APIs

**Currency Conversion:**
- Real-time exchange rates
- 150+ currency pairs
- Historical rate lookup

**Timezone Services:**
- Global timezone directory
- Timezone detection from coordinates
- Timezone-aware date conversion

**Address Tools:**
- Address validation
- Autocomplete suggestions
- Format standardization

---

## SECTION 2: BUSINESS MODEL

### 2.1 Subscription & Pricing Structure

**Model:** Credit-based SaaS
- Users subscribe to a plan to receive a monthly credit allocation
- Each API call consumes a set number of credits (varies by endpoint)
- Credits can be topped up anytime without upgrading plans
- Monthly or yearly billing available (yearly provides cost savings)

**Why Credit-Based (Sales Pitch Point):**
- Predictable costs for customers (no per-call billing surprises)
- Fair usage control (heavier users are incentivized to upgrade)
- Easy upsell path (top-ups are frictionless)
- No need to track individual endpoint pricing

**Coupon & Discount System:**
- Fixed-amount or percentage-based discounts
- Per-plan applicability (e.g., only on Business plan)
- Redemption limits (single-use or limited multi-use)
- Maximum discount caps
- Excellent tool for: partner promotions, conference offers, sales trials, referral programs

**Payment Infrastructure:**
- Razorpay integration — supports UPI, Credit/Debit Cards, Net Banking, Wallets
- Covers 99%+ of Indian payment preferences
- Automated subscription renewal
- Webhook-verified payment events
- Transaction history and receipts for GST compliance

### 2.2 Revenue Levers

| Revenue Stream | How It Works |
|---|---|
| Subscription MRR | Monthly recurring revenue from plan subscriptions |
| Annual Upfront | Yearly plans paid upfront (improved cash flow, customer retention) |
| Credit Top-Ups | One-time purchases for customers exceeding their monthly allocation |
| Enterprise Contracts | Custom pricing, volume discounts, SLA commitments |
| Partner/Reseller | White-label or embedded API for platform partners |

---

## SECTION 3: TARGET MARKET ANALYSIS

### 3.1 Ideal Customer Profiles (ICPs)

**ICP 1: Fintech Startup (Series A–B)**
- Size: 20–200 employees
- Tech team: 5–30 engineers
- Needs: Payment validation, KYC automation, market data for dashboards
- Decision maker: CTO / VP Engineering
- Budget: ₹10K–₹1L/month for data infrastructure
- Pain: Maintaining multiple vendor relationships, inconsistent data quality

**ICP 2: Investment / Wealth Platform**
- Size: Series A or bootstrapped profitable
- Products: Stock broker, MFD platform, robo-advisor, portfolio tracker
- Needs: Equity prices, mutual fund NAVs, index data
- Decision maker: Product Head / CTO
- Budget: ₹25K–₹5L/month for market data
- Pain: Direct exchange data is expensive and complex; existing vendors have poor APIs

**ICP 3: E-Commerce / Logistics Company**
- Size: 50–500 employees
- Products: Online marketplace, last-mile delivery platform, D2C brand
- Needs: Pincode database, address validation, delivery zone mapping
- Decision maker: CTO / Engineering Manager
- Budget: ₹5K–₹50K/month
- Pain: Open-source pincode data is stale, unreliable, or poorly formatted

**ICP 4: Bank / NBFC / Insurance Company**
- Size: Medium to large enterprise
- Needs: IFSC validation, branch network intelligence, KYC OCR
- Decision maker: VP Technology / Head of Digital
- Budget: ₹1L–₹10L/month
- Pain: Legacy systems, slow internal data teams, compliance pressure to modernize KYC

**ICP 5: B2B SaaS Platform (Building for Indian market)**
- Size: Any — building India-specific features
- Needs: Address data, banking data as a feature of their own product
- Decision maker: Founder / CTO
- Budget: ₹5K–₹50K/month
- Pain: Spending engineering time on data that isn't their core IP

### 3.2 Market Size Estimates

| Segment | Potential Buyers in India | Avg. Monthly Value | TAM |
|---|---|---|---|
| Fintech Startups | 7,500+ | ₹15,000 | ₹112 Cr/yr |
| Investment Apps | 500+ | ₹50,000 | ₹30 Cr/yr |
| E-Commerce / Logistics | 10,000+ | ₹8,000 | ₹96 Cr/yr |
| Banks / NBFCs / Insurance | 2,000+ | ₹75,000 | ₹180 Cr/yr |
| B2B SaaS | 5,000+ | ₹10,000 | ₹60 Cr/yr |
| **Total Addressable Market** | | | **~₹478 Cr/yr** |

*Conservative estimates based on similar data API pricing in Indian market*

---

## SECTION 4: COMPETITIVE LANDSCAPE

### 4.1 Direct Competitors

| Competitor | Focus Area | Gap vs SetuGeo |
|---|---|---|
| MapMyIndia | Mapping & geospatial | No financial data, no banking IFSC, no OCR |
| RazorpayX Data | Payment infrastructure | Limited to payments, no geo/market data |
| Postman (pincode APIs) | Geo data only | No financial market, no OCR, less reliable |
| NSE Data APIs | Equity data only | No geo, no banking, complex licensing |
| AMFI Data Feed | Mutual fund NAV only | Raw data, no value-added APIs |
| Individual OCR vendors | Document OCR only | No geo or market data |

### 4.2 SetuGeo's Competitive Moat

1. **Breadth:** No single competitor covers all 5 modules (geo + banking + equity + MF + OCR)
2. **India Depth:** Competitors with global coverage sacrifice India-specific depth (IFSC, BSE, all AMCs)
3. **API Quality:** Clean, versioned REST API with consistent JSON — many Indian data providers have poor API design
4. **Integrated Billing:** Credit-based system with topups is more flexible than per-endpoint pricing
5. **All-in-One Contract:** One vendor, one integration, one support channel

---

## SECTION 5: SALES PLAYBOOK

### 5.1 Discovery Questions

**For E-Commerce / Logistics:**
- "How do you currently validate that a customer's pincode is serviceable?"
- "How many hours a month does your team spend maintaining your pincode database?"
- "What happens when a customer enters an invalid address at checkout?"

**For Fintech / Payments:**
- "How do you validate IFSC codes before initiating bank transfers?"
- "What's the cost (support, refunds) when a payment fails due to wrong IFSC?"
- "How long does your KYC process take end-to-end today?"

**For Investment / Wealth Platforms:**
- "Where do you currently get your equity and mutual fund data?"
- "How many vendors do you have for market data today? What's the total cost?"
- "What's your integration effort been for each data source?"

**For Banks / NBFCs / Insurance:**
- "How much does your manual KYC team process per day?"
- "What's your cost per KYC document today?"
- "How are you currently doing IFSC validation for disbursements?"

### 5.2 Objection Handling

**"We'll build it ourselves."**
> "Absolutely, you could. Our customers who've done this estimate 3–6 months of engineering time, plus ongoing maintenance for data freshness. Our platform is production-ready today. What would your team build instead if they had those months back?"

**"This data is available for free online."**
> "Free sources (GitHub pincode repos, AMFI CSVs) are point-in-time exports that go stale and aren't structured for API consumption. Our platform keeps data fresh automatically and serves it with sub-100ms API response times. What's your plan for keeping that free data up to date?"

**"We already have a market data vendor."**
> "We'd love to see what you're paying and what you're getting. Most of our customers who switched were paying more for less — often without geo or banking data bundled in. Would a 30-minute comparison be useful?"

**"Your pricing seems high."**
> "Let's calculate your actual cost. What are you paying your current vendors combined? Add the engineering hours to maintain those integrations. Add the cost of any data quality issues. SetuGeo's total cost of ownership is almost always lower. Want to work through the numbers together?"

**"We're concerned about data reliability."**
> "Fair concern — let's talk about our data pipeline. [Describe sync jobs, update frequency, monitoring]. We'd also suggest a trial period so you can validate quality against your existing data source before committing."

### 5.3 Sales Process

**Stage 1: Discovery (30 min call)**
- Understand current data stack
- Identify which of the 5 modules are relevant
- Quantify current pain (time, cost, quality)

**Stage 2: Demo (45–60 min)**
- Live API demo using their use case
- Show logs dashboard (builds confidence in transparency)
- Walk through pricing with credit calculator

**Stage 3: Trial (1–2 weeks)**
- Provide starter credits
- Dedicated onboarding support
- Check-in on integration progress

**Stage 4: Proposal**
- Right-size plan based on trial usage
- Include multi-year discount if appropriate
- Offer implementation support for enterprise

**Stage 5: Close**
- Razorpay payment (frictionless, all Indian methods)
- API key delivered instantly
- Onboarding documentation + support ticket access

---

## SECTION 6: TECHNICAL CREDIBILITY

### 6.1 Architecture Overview

```
┌─────────────────────────────────────────────────────────┐
│                    CLIENT APPLICATION                   │
└────────────────────────┬────────────────────────────────┘
                         │ HTTPS REST API
                         ▼
┌─────────────────────────────────────────────────────────┐
│              SETUGEO API GATEWAY (Laravel 9)            │
│  • Token Auth (Sanctum)  • Rate Limiting (Credits)      │
│  • API Logging           • Request Routing              │
└──────┬──────────────┬──────────────┬────────────────────┘
       │              │              │
       ▼              ▼              ▼
┌──────────┐  ┌──────────────┐  ┌──────────────┐
│ MySQL DB │  │  OCR Service │  │  Razorpay    │
│ (Core    │  │  (FastAPI/   │  │  (Payments)  │
│  Data)   │  │  Tesseract)  │  │              │
└──────────┘  └──────────────┘  └──────────────┘
```

### 6.2 Security & Compliance

- **Authentication:** Laravel Sanctum (Bearer token per user)
- **API Keys:** Client key + secret pair, regenerable anytime
- **Payment Security:** Razorpay webhook signature verification (prevents replay attacks)
- **OCR Privacy:** Documents processed in memory, never persisted
- **Role Isolation:** Admin and user roles strictly separated
- **Rate Limiting:** Credit-based system prevents abuse without hard rate caps

### 6.3 Data Freshness

| Data Type | Update Frequency |
|---|---|
| Equity Prices | Daily (market close) |
| Mutual Fund NAVs | Daily (AMFI publish) |
| Index Values | Daily (market close) |
| Currency Rates | Daily / Real-time |
| Geographic Data | As-needed (stable) |
| Bank/Branch Data | As-needed (RBI updates) |

### 6.4 Developer Experience

- Clean RESTful API — all endpoints follow consistent patterns
- Paginated responses for all list endpoints
- JSON throughout — no XML, no CSV parsing
- Interactive API logs in dashboard (see exactly what was called, when, credits used)
- Usage analytics by endpoint (know which APIs you're actually using)
- API versioning (/api/v1/) — future versions won't break existing integrations

---

## SECTION 7: MARKETING & GROWTH OPPORTUNITIES

### 7.1 Content Marketing

- **India Pincode Database blog post:** SEO magnet for developers searching "india pincode api"
- **IFSC validation guide:** Fintech developers Google this constantly
- **"Build a mutual fund tracker in 1 hour":** Tutorial using SetuGeo API
- **Indian market data API comparison:** Positions SetuGeo vs. alternatives

### 7.2 Partnership Channels

- **Developer Communities:** GFG, IndiaHacks, developer.in communities
- **Fintech Accelerators:** Y Combinator India, 100X.VC, Sequoia Surge — reach portfolio companies
- **Tech Stack Partners:** Companies whose developers would benefit (hosting, auth providers)
- **System Integrators:** Agencies building fintech / e-commerce solutions for clients

### 7.3 PLG (Product-Led Growth) Levers

- Free tier with enough credits to integrate and demonstrate value
- Self-serve signup → instant API key (no sales call required for SMB)
- Interactive API explorer in documentation
- Usage alerts before credits expire (avoid bad churn due to surprise outage)
- In-app upgrade prompts when usage approaches limit

### 7.4 Enterprise Sales Motion

- Direct outreach to CTOs/VPs at fintech companies
- LinkedIn + cold email sequence targeting India's top 500 fintech companies
- Conference presence: Global Fintech Fest, Nasscom Product Conclave
- Reference customer case study → warm intro pipeline

---

## SECTION 8: FINANCIAL PROJECTIONS (ILLUSTRATIVE)

### 8.1 Early Stage ARR Build

| Month | Customers | Avg MRR/Customer | MRR | ARR |
|---|---|---|---|---|
| M1 | 10 | ₹5,000 | ₹50,000 | ₹6L |
| M3 | 30 | ₹8,000 | ₹2,40,000 | ₹29L |
| M6 | 75 | ₹10,000 | ₹7,50,000 | ₹90L |
| M12 | 150 | ₹15,000 | ₹22,50,000 | ₹2.7 Cr |

### 8.2 Unit Economics

| Metric | Target |
|---|---|
| CAC (Customer Acquisition Cost) | ₹5,000–₹15,000 |
| Avg. Contract Value (ACV) | ₹1,20,000/yr |
| LTV (assuming 24-month retention) | ₹2,40,000 |
| LTV:CAC Ratio | 16x–48x |
| Gross Margin | 70–80% (API business) |
| Payback Period | 1–2 months |

---

## SECTION 9: KEY MESSAGING

### Taglines by Audience

**For Developers:**
> "The API for everything your India app needs. Location. Banking. Markets. Documents."

**For CTOs:**
> "Replace 5 vendors with 1 integration. Ship faster. Spend less."

**For Product Managers:**
> "Production-ready data infrastructure. No data licensing headaches. No maintenance."

**For CEOs / Founders:**
> "Your team should be building your product, not maintaining data pipelines."

### Core Value Statements (in 10 seconds or less)

1. "SetuGeo gives you India's geographic, banking, and financial market data through a single REST API."
2. "Stop maintaining pincode databases. SetuGeo keeps them fresh — you just call the API."
3. "Get NSE/BSE market data without the exchange membership fees or compliance overhead."
4. "Automate your KYC document processing. Our OCR API handles PAN, Aadhaar, Driving License, and more."

---

## APPENDIX: QUICK REFERENCE

### API Module Summary

| Module | Endpoints | Primary Buyers |
|---|---|---|
| Geospatial | 20+ | E-commerce, Logistics, Real Estate |
| Banking | 15+ | Fintech, Lending, Insurance |
| Equities | 20+ | Investment Apps, Wealth Platforms |
| Mutual Funds | 15+ | MFDs, Robo-advisors, Portfolio Trackers |
| Indices | 10+ | Market Apps, Financial Dashboards |
| OCR | 5+ | Lending, Insurance, KYC-intensive businesses |
| Utilities | 10+ | Any India-facing app |
| **TOTAL** | **95+** | |

### Technology at a Glance

- **Backend:** Laravel 9, PHP 8
- **Database:** MySQL
- **Auth:** Laravel Sanctum (Bearer tokens)
- **Payments:** Razorpay
- **OCR Engine:** Tesseract (via FastAPI microservice)
- **API Style:** REST, JSON, versioned (/api/v1/)
- **Deployment:** Linux/LAMP compatible

---

*Report compiled: May 2026 | SetuGeo | setugeo.com*
*For internal sales use and qualified prospect sharing*
