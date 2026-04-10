# SetuGeo API — Load Testing Setup Guide
# ========================================

## Prerequisites

```bash
# Install Artillery globally
npm install -g artillery

# Verify installation
artillery --version
```

## Step 1: Get Your Bearer Token

Before running load tests, you need a valid API token:

```bash
curl -X POST http://localhost:8000/api/v1/auth/token \
  -H "Content-Type: application/json" \
  -d '{"client_key":"YOUR_CLIENT_KEY","client_secret":"YOUR_CLIENT_SECRET"}'
```

Copy the `access_token` from the response.

## Step 2: Update Configuration

Edit `artillery-config.yml` and replace:
- `YOUR_BEARER_TOKEN_HERE` → your actual bearer token
- `countryId`, `stateId`, `cityId` → valid IDs from your database

## Step 3: Run Tests

### Standard Load Test
```bash
cd d:\xampp\htdocs\GeoData
artillery run tests/LoadTest/artillery-config.yml
```

### Stress Test (Breaking Point)
```bash
artillery run tests/LoadTest/artillery-stress.yml
```

### Generate HTML Report
```bash
artillery run --output results.json tests/LoadTest/artillery-config.yml
artillery report results.json --output load-test-report.html
```

## Step 4: Reading Results

Key metrics to monitor:
- **http.response_time.median** — Should be < 500ms  
- **http.response_time.p95** — Should be < 1000ms
- **http.response_time.p99** — Should be < 2000ms
- **http.codes.200** — Success count
- **http.codes.429** — Rate limiting hits
- **http.codes.500** — Server errors (should be 0)

## Environment Variables (Optional)

You can use environment variables instead of hardcoding:

```bash
set CLIENT_KEY=ck_your_key
set CLIENT_SECRET=secret_your_secret
artillery run tests/LoadTest/artillery-config.yml
```
