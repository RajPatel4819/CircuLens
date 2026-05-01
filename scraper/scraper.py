#!/usr/bin/env python3
"""
CircuLens GTU Circular Scraper
Scrapes circulars from GTU website and stores them in the database.
"""

import os
import re
import hashlib
import logging
import requests
from datetime import datetime
from urllib.parse import urljoin, urlparse

from bs4 import BeautifulSoup
from dotenv import load_dotenv

from nlp_analyzer import classify_circular
from notifier import send_notifications

load_dotenv()

logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s [%(levelname)s] %(message)s',
    handlers=[
        logging.FileHandler('scraper.log'),
        logging.StreamHandler()
    ]
)
logger = logging.getLogger(__name__)

# Configuration from .env
GTU_BASE_URL = os.getenv('GTU_BASE_URL', 'https://www.gtu.ac.in')
GTU_CIRCULAR_PATH = os.getenv('GTU_CIRCULAR_PATH', '/circular.htm')
DB_HOST = os.getenv('DB_HOST', 'localhost')
DB_NAME = os.getenv('DB_NAME', 'circulens')
DB_USER = os.getenv('DB_USER', 'root')
DB_PASS = os.getenv('DB_PASS', '')
UPLOAD_DIR = os.getenv('UPLOAD_DIR', '../assets/uploads/')
REQUEST_TIMEOUT = int(os.getenv('REQUEST_TIMEOUT', '30'))
MAX_CIRCULARS = int(os.getenv('MAX_CIRCULARS', '50'))

HEADERS = {
    'User-Agent': 'Mozilla/5.0 (compatible; CircuLens/1.0; +https://circulens.app)',
    'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
}


def get_db_connection():
    """Get MySQL database connection."""
    try:
        import mysql.connector
        conn = mysql.connector.connect(
            host=DB_HOST,
            database=DB_NAME,
            user=DB_USER,
            password=DB_PASS,
            charset='utf8mb4',
            collation='utf8mb4_unicode_ci',
        )
        return conn
    except ImportError:
        logger.error("mysql-connector-python not installed. Run: pip install mysql-connector-python")
        raise
    except Exception as e:
        logger.error(f"Database connection failed: {e}")
        raise


def compute_hash(text: str) -> str:
    """Compute SHA-256 hash of text content."""
    return hashlib.sha256(text.encode('utf-8')).hexdigest()


def circular_exists(cursor, content_hash: str) -> bool:
    """Check if a circular with this hash already exists."""
    cursor.execute('SELECT id FROM circulars WHERE content_hash = %s LIMIT 1', (content_hash,))
    return cursor.fetchone() is not None


def fetch_page(url: str) -> BeautifulSoup | None:
    """Fetch and parse a web page."""
    try:
        resp = requests.get(url, headers=HEADERS, timeout=REQUEST_TIMEOUT)
        resp.raise_for_status()
        resp.encoding = resp.apparent_encoding or 'utf-8'
        return BeautifulSoup(resp.text, 'html.parser')
    except requests.RequestException as e:
        logger.error(f"Failed to fetch {url}: {e}")
        return None


def download_pdf(pdf_url: str, filename: str) -> str | None:
    """Download a PDF file and save it to the uploads directory."""
    try:
        os.makedirs(UPLOAD_DIR, exist_ok=True)
        dest_path = os.path.join(UPLOAD_DIR, filename)

        if os.path.exists(dest_path):
            return filename

        resp = requests.get(pdf_url, headers=HEADERS, timeout=REQUEST_TIMEOUT, stream=True)
        resp.raise_for_status()

        content_type = resp.headers.get('Content-Type', '')
        if 'pdf' not in content_type.lower() and not pdf_url.lower().endswith('.pdf'):
            logger.warning(f"URL may not be a PDF: {pdf_url}")

        with open(dest_path, 'wb') as f:
            for chunk in resp.iter_content(chunk_size=8192):
                f.write(chunk)

        logger.info(f"Downloaded PDF: {filename}")
        return filename
    except Exception as e:
        logger.error(f"Failed to download PDF {pdf_url}: {e}")
        return None


def parse_circulars(soup: BeautifulSoup, base_url: str) -> list[dict]:
    """Parse circulars from GTU page."""
    circulars = []

    # Try multiple selectors for GTU's page structure
    selectors = [
        'table.table tr',
        '.circular-list li',
        'div.content table tr',
        'table tr',
    ]

    rows = []
    for sel in selectors:
        rows = soup.select(sel)
        if len(rows) > 2:
            break

    for row in rows[:MAX_CIRCULARS]:
        try:
            # Find link and text
            links = row.find_all('a', href=True)
            if not links:
                continue

            link     = links[0]
            title    = link.get_text(strip=True)
            href     = link['href']
            full_url = urljoin(base_url, href)

            if not title or len(title) < 5:
                continue

            # Extract description from row text
            row_text    = row.get_text(separator=' ', strip=True)
            description = row_text[:500] if len(row_text) > len(title) else title

            # Extract date if available
            date_pattern = re.compile(r'\d{1,2}[/-]\d{1,2}[/-]\d{2,4}|\d{4}-\d{2}-\d{2}')
            date_match   = date_pattern.search(row_text)
            pub_date     = date_match.group(0) if date_match else None

            is_pdf   = href.lower().endswith('.pdf')
            pdf_url  = full_url if is_pdf else None
            page_url = None if is_pdf else full_url

            circulars.append({
                'title':       title[:255],
                'description': description,
                'source_url':  full_url,
                'pdf_url':     pdf_url,
                'page_url':    page_url,
                'pub_date':    pub_date,
            })
        except Exception as e:
            logger.debug(f"Error parsing row: {e}")
            continue

    return circulars


def save_circular(cursor, conn, circular: dict) -> int | None:
    """Save a circular to the database. Returns new circular ID or None if duplicate."""
    title       = circular['title']
    description = circular.get('description', '')
    source_url  = circular.get('source_url', '')
    pdf_path    = circular.get('saved_pdf')

    content_hash = compute_hash(title + (description or ''))

    if circular_exists(cursor, content_hash):
        logger.debug(f"Duplicate circular skipped: {title[:60]}")
        return None

    # Classify using NLP
    circular_type = classify_circular(title, description)

    cursor.execute(
        '''INSERT INTO circulars
           (title, description, pdf_path, circular_type, source, source_url, content_hash, is_active)
           VALUES (%s, %s, %s, %s, 'scraped', %s, %s, 1)''',
        (title, description, pdf_path, circular_type, source_url, content_hash)
    )
    conn.commit()
    new_id = cursor.lastrowid
    logger.info(f"Saved circular [{circular_type}]: {title[:70]}")
    return new_id


def scrape_gtu() -> list[int]:
    """Main scraping function. Returns list of new circular IDs."""
    url = GTU_BASE_URL + GTU_CIRCULAR_PATH
    logger.info(f"Scraping GTU circulars from: {url}")

    soup = fetch_page(url)
    if not soup:
        logger.error("Failed to fetch GTU page. Aborting.")
        return []

    raw_circulars = parse_circulars(soup, GTU_BASE_URL)
    logger.info(f"Found {len(raw_circulars)} candidate circulars on page")

    if not raw_circulars:
        logger.warning("No circulars found on page. GTU page structure may have changed.")
        return []

    try:
        conn   = get_db_connection()
        cursor = conn.cursor(dictionary=True)
    except Exception:
        return []

    new_ids = []
    try:
        for circ in raw_circulars:
            # Download PDF if available
            if circ.get('pdf_url'):
                safe_name  = re.sub(r'[^a-zA-Z0-9_-]', '_', circ['title'][:40])
                ts         = datetime.now().strftime('%Y%m%d%H%M%S')
                filename   = f"gtu_{safe_name}_{ts}.pdf"
                saved      = download_pdf(circ['pdf_url'], filename)
                circ['saved_pdf'] = saved

            new_id = save_circular(cursor, conn, circ)
            if new_id:
                new_ids.append(new_id)

        logger.info(f"Scraping complete. {len(new_ids)} new circulars added.")
    except Exception as e:
        logger.error(f"Error during scraping: {e}")
    finally:
        cursor.close()
        conn.close()

    return new_ids


if __name__ == '__main__':
    new_circular_ids = scrape_gtu()
    if new_circular_ids:
        logger.info(f"Triggering notifications for {len(new_circular_ids)} new circulars...")
        send_notifications(new_circular_ids)
    else:
        logger.info("No new circulars to notify about.")
