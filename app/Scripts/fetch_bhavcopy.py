import sys
import os
import requests
import zipfile
import io
import csv
import json
from datetime import datetime
import time

def log(message):
    sys.stderr.write(f"{message}\n")

def fetch_url(url, retries=3):
    headers = {
        'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
        'Referer': 'https://www.nseindia.com/all-reports'
    }
    for i in range(retries):
        try:
            response = requests.get(url, headers=headers, timeout=20)
            if response.status_code == 200:
                return response.content
            if response.status_code == 404:
                return None
        except Exception as e:
            log(f"Retry {i+1} for {url} failed: {str(e)}")
            time.sleep(2)
    return None

def get_nse_bhavcopy(date_obj):
    year = date_obj.strftime('%Y')
    month_upper = date_obj.strftime('%b').upper()
    date_str = date_obj.strftime('%d%b%Y').upper()
    date_underscore = date_obj.strftime('%Y%m%d')

    urls = [
        f"https://nsearchives.nseindia.com/content/cm/BhavCopy_NSE_CM_0_0_0_{date_underscore}_F_0000.csv.zip",
        f"https://archives.nseindia.com/content/historical/EQUITIES/{year}/{month_upper}/cm{date_str}bhav.csv.zip",
        f"https://www.nseindia.com/content/historical/EQUITIES/{year}/{month_upper}/cm{date_str}bhav.csv.zip",
    ]
    
    for url in urls:
        content = fetch_url(url)
        if content:
            try:
                if url.lower().endswith('.zip'):
                    with zipfile.ZipFile(io.BytesIO(content)) as z:
                        for name in z.namelist():
                            if name.lower().endswith('.csv'):
                                decoded = z.read(name).decode('utf-8-sig')
                                return list(csv.DictReader(io.StringIO(decoded)))
                else:
                    decoded = content.decode('utf-8-sig')
                    return list(csv.DictReader(io.StringIO(decoded)))
            except Exception as e:
                log(f"Error parsing NSE from {url}: {str(e)}")
    return None

def get_bse_bhavcopy(date_obj):
    date_str = date_obj.strftime('%d%m%y')
    date_underscore = date_obj.strftime('%Y%m%d')
    
    urls = [
        f"https://www.bseindia.com/download/BhavCopy/Equity/BhavCopy_BSE_CM_0_0_0_{date_underscore}_F_0000.CSV",
        f"https://www.bseindia.com/download/BhavCopy/Equity/EQ{date_str}_CSV.ZIP"
    ]
    
    for url in urls:
        content = fetch_url(url)
        if content:
            try:
                if url.lower().endswith('.zip'):
                    with zipfile.ZipFile(io.BytesIO(content)) as z:
                        for name in z.namelist():
                            if name.lower().endswith('.csv'):
                                raw = z.read(name)
                                try:
                                    decoded = raw.decode('utf-8-sig')
                                except:
                                    decoded = raw.decode('ansi')
                                return list(csv.DictReader(io.StringIO(decoded)))
                else:
                    try:
                        decoded = content.decode('utf-8-sig')
                    except:
                        decoded = content.decode('ansi')
                    return list(csv.DictReader(io.StringIO(decoded)))
            except Exception as e:
                log(f"Error parsing BSE from {url}: {str(e)}")
    return None

def get_mapped_value(row, candidates, default=0):
    # Clean row keys (strip spaces)
    row = { (k.strip() if k else k): (v.strip() if v else v) for k, v in row.items() }
    for cand in candidates:
        if cand in row: return row[cand]
        if cand.upper() in row: return row[cand.upper()]
        if cand.lower() in row: return row[cand.lower()]
    return default

def main():
    date_str = sys.argv[1] if len(sys.argv) > 1 else datetime.now().strftime('%Y-%m-%d')
    date_obj = datetime.strptime(date_str, '%Y-%m-%d')
    exchange_filter = sys.argv[2].upper() if len(sys.argv) > 2 else None
    
    # Column mappings for different formats (Traditional & UDiFF)
    mapper = {
        'isin': ['ISIN', 'FinInstrmId', 'ISIN_CODE'],
        'symbol': ['TckrSymb', 'SYMBOL', 'SC_NAME'],
        'name': ['FinInstrmNm', 'COMPANY_NAME', 'FULL_NAME'],
        'open': ['OpnPric', 'OPEN', 'OPEN_PRC'],
        'high': ['HghPric', 'HIGH', 'HIGH_PRC'],
        'low': ['LwPric', 'LOW', 'LOW_PRC'],
        'close': ['ClsPric', 'CLOSE', 'CLOSE_PRC', 'LAST_PRC', 'LAST'],
        'last': ['LastPric', 'LAST', 'LTP', 'LAST_PRC'],
        'prev': ['PrvsClsgPric', 'PREVCLOSE', 'PREV_CLOSE', 'PREV_CLSG_PRC'],
        'volume': ['TtlTradgVol', 'TOTTRDQTY', 'NO_SHARES', 'TOT_TR_QTY', 'TRADE_QTY'],
        'turnover': ['TtlTradgVal', 'TOTTRDVAL', 'NET_TURNOV'],
        'trades': ['TtlNbOfTradesExecuted', 'TOTALTRADES', 'NO_OF_TRDS'],
        'avg_price': ['WghtdAvgPric', 'AVG_PRICE', 'AVG_PRC']
    }

    results = []
    
    if not exchange_filter or exchange_filter == 'NSE':
        data = get_nse_bhavcopy(date_obj)
        if data:
            for row in data:
                isin = get_mapped_value(row, mapper['isin'], None)
                close = get_mapped_value(row, mapper['close'], None)
                if isin and close:
                    try:
                        results.append({
                            'isin': isin,
                            'symbol': get_mapped_value(row, mapper['symbol'], ''),
                            'name': get_mapped_value(row, mapper['name'], ''),
                            'open': float(get_mapped_value(row, mapper['open'])),
                            'high': float(get_mapped_value(row, mapper['high'])),
                            'low': float(get_mapped_value(row, mapper['low'])),
                            'close': float(close),
                            'last': float(get_mapped_value(row, mapper['last'])),
                            'prev_close': float(get_mapped_value(row, mapper['prev'])),
                            'volume': int(float(get_mapped_value(row, mapper['volume']))),
                            'turnover': float(get_mapped_value(row, mapper['turnover'])),
                            'trades': int(float(get_mapped_value(row, mapper['trades']))),
                            'avg_price': float(get_mapped_value(row, mapper['avg_price'])),
                            'exchange': 'NSE'
                        })
                    except: continue
                    
    if not exchange_filter or exchange_filter == 'BSE':
        data = get_bse_bhavcopy(date_obj)
        if data:
            for row in data:
                isin = get_mapped_value(row, mapper['isin'], None)
                close = get_mapped_value(row, mapper['close'], None)
                if isin and close:
                    try:
                        results.append({
                            'isin': isin,
                            'symbol': get_mapped_value(row, mapper['symbol'], ''),
                            'name': get_mapped_value(row, mapper['name'], ''),
                            'open': float(get_mapped_value(row, mapper['open'])),
                            'high': float(get_mapped_value(row, mapper['high'])),
                            'low': float(get_mapped_value(row, mapper['low'])),
                            'close': float(close),
                            'last': float(get_mapped_value(row, mapper['last'])),
                            'prev_close': float(get_mapped_value(row, mapper['prev'])),
                            'volume': int(float(get_mapped_value(row, mapper['volume']))),
                            'turnover': float(get_mapped_value(row, mapper['turnover'])),
                            'trades': int(float(get_mapped_value(row, mapper['trades']))),
                            'avg_price': float(get_mapped_value(row, mapper['avg_price'])),
                            'exchange': 'BSE'
                        })
                    except: continue

    print(json.dumps(results))

if __name__ == "__main__":
    main()
