#!/usr/bin/env python3
"""
CircuLens Notifier
Sends email notifications to users for new circulars using SMTP.
"""

import os
import logging
import smtplib
from datetime import datetime
from email.mime.multipart import MIMEMultipart
from email.mime.text import MIMEText
from typing import Optional

from dotenv import load_dotenv

from matcher import create_notifications_for_circular, get_db_connection

load_dotenv()

logger = logging.getLogger(__name__)

# Email config from .env
MAIL_HOST      = os.getenv('MAIL_HOST',      'smtp.gmail.com')
MAIL_PORT      = int(os.getenv('MAIL_PORT',  '587'))
MAIL_USER      = os.getenv('MAIL_USERNAME',  '')
MAIL_PASS      = os.getenv('MAIL_PASSWORD',  '')
MAIL_FROM_NAME = os.getenv('MAIL_FROM_NAME', 'CircuLens')
APP_URL        = os.getenv('APP_URL',        'http://localhost/CircuLens')


def build_email_html(user_name: str, circulars: list[dict]) -> str:
    """Build HTML email body."""
    items_html = ''
    type_colors = {
        'academic':    '#2563eb',
        'examination': '#dc2626',
        'events':      '#16a34a',
        'placement':   '#7c3aed',
        'timetable':   '#ca8a04',
        'general':     '#6b7280',
    }

    for c in circulars:
        color = type_colors.get(c.get('circular_type', 'general'), '#6b7280')
        items_html += f"""
        <div style="border:1px solid #e5e7eb;border-radius:8px;padding:16px;margin-bottom:12px;">
            <span style="background:{color}20;color:{color};padding:2px 10px;border-radius:20px;font-size:12px;font-weight:600;">
                {(c.get('circular_type') or 'general').upper()}
            </span>
            <h3 style="margin:8px 0 6px;font-size:16px;color:#1f2937;">{c.get('title', '')}</h3>
            <p style="color:#6b7280;font-size:14px;margin:0 0 8px;">{(c.get('description') or '')[:200]}...</p>
            <a href="{APP_URL}" style="color:#2563eb;font-size:13px;text-decoration:none;">View Circular →</a>
        </div>
        """

    return f"""
    <!DOCTYPE html>
    <html>
    <head><meta charset="UTF-8"></head>
    <body style="font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:#f3f4f6;margin:0;padding:20px;">
    <div style="max-width:600px;margin:0 auto;background:white;border-radius:12px;overflow:hidden;box-shadow:0 4px 6px rgba(0,0,0,.07);">
        <div style="background:linear-gradient(135deg,#1e3a8a,#1d4ed8);padding:32px;text-align:center;">
            <span style="color:#fb923c;font-size:28px;font-weight:800;">Circu</span>
            <span style="color:white;font-size:28px;font-weight:800;">Lens</span>
            <p style="color:#93c5fd;margin:8px 0 0;font-size:14px;">GTU Circular Management System</p>
        </div>
        <div style="padding:32px;">
            <h2 style="color:#1f2937;margin:0 0 8px;">Hello, {user_name}! 👋</h2>
            <p style="color:#6b7280;font-size:15px;margin:0 0 24px;">
                {len(circulars)} new circular{'s' if len(circulars) > 1 else ''} matching your preferences:
            </p>
            {items_html}
            <div style="text-align:center;margin-top:24px;">
                <a href="{APP_URL}/user/dashboard.php"
                   style="background:#1d4ed8;color:white;padding:12px 28px;border-radius:8px;text-decoration:none;font-weight:600;font-size:15px;">
                    View Your Dashboard
                </a>
            </div>
        </div>
        <div style="background:#f9fafb;padding:16px 32px;text-align:center;border-top:1px solid #e5e7eb;">
            <p style="color:#9ca3af;font-size:12px;margin:0;">
                You're receiving this because you registered at CircuLens.<br>
                <a href="{APP_URL}/user/preferences.php" style="color:#6b7280;">Manage preferences</a> ·
                <a href="{APP_URL}/user/logout.php" style="color:#6b7280;">Unsubscribe</a>
            </p>
        </div>
    </div>
    </body>
    </html>
    """


def send_email(to_email: str, to_name: str, subject: str, html_body: str) -> bool:
    """Send an email via SMTP."""
    if not MAIL_USER or not MAIL_PASS:
        logger.warning("Email credentials not configured. Skipping email send.")
        return False

    try:
        msg = MIMEMultipart('alternative')
        msg['Subject'] = subject
        msg['From']    = f"{MAIL_FROM_NAME} <{MAIL_USER}>"
        msg['To']      = f"{to_name} <{to_email}>"

        msg.attach(MIMEText(html_body, 'html', 'utf-8'))

        with smtplib.SMTP(MAIL_HOST, MAIL_PORT) as server:
            server.ehlo()
            server.starttls()
            server.login(MAIL_USER, MAIL_PASS)
            server.sendmail(MAIL_USER, to_email, msg.as_string())

        logger.info(f"Email sent to {to_email}")
        return True
    except smtplib.SMTPException as e:
        logger.error(f"SMTP error sending to {to_email}: {e}")
        return False
    except Exception as e:
        logger.error(f"Failed to send email to {to_email}: {e}")
        return False


def send_notifications(new_circular_ids: list[int]) -> dict:
    """
    For each new circular, match users and send email notifications.
    Returns stats dict.
    """
    stats = {'emails_sent': 0, 'emails_failed': 0, 'notifications_created': 0}

    if not new_circular_ids:
        return stats

    # First create notification records
    for cid in new_circular_ids:
        count = create_notifications_for_circular(cid)
        stats['notifications_created'] += count

    # Now send emails for pending notifications
    try:
        conn   = get_db_connection()
        cursor = conn.cursor(dictionary=True)
    except Exception as e:
        logger.error(f"DB connection failed: {e}")
        return stats

    try:
        # Get all unsent notifications grouped by user
        cursor.execute(
            '''SELECT n.id AS notif_id, n.user_id, n.circular_id,
                      u.email, u.name,
                      c.title, c.description, c.circular_type
               FROM notifications n
               JOIN users u ON u.id = n.user_id
               JOIN circulars c ON c.id = n.circular_id
               WHERE n.is_sent = 0 AND n.circular_id IN ({})
               ORDER BY n.user_id'''.format(
                ','.join(['%s'] * len(new_circular_ids))
            ),
            new_circular_ids
        )
        rows = cursor.fetchall()

        # Group by user
        by_user: dict[int, dict] = {}
        for row in rows:
            uid = row['user_id']
            if uid not in by_user:
                by_user[uid] = {
                    'email':     row['email'],
                    'name':      row['name'],
                    'notif_ids': [],
                    'circulars': [],
                }
            by_user[uid]['notif_ids'].append(row['notif_id'])
            by_user[uid]['circulars'].append({
                'title':         row['title'],
                'description':   row['description'],
                'circular_type': row['circular_type'],
            })

        for uid, data in by_user.items():
            count    = len(data['circulars'])
            subject  = f"CircuLens: {count} new circular{'s' if count > 1 else ''} for you"
            html     = build_email_html(data['name'], data['circulars'])
            sent     = send_email(data['email'], data['name'], subject, html)

            if sent:
                stats['emails_sent'] += 1
                placeholders = ','.join(['%s'] * len(data['notif_ids']))
                cursor.execute(
                    f"UPDATE notifications SET is_sent = 1, sent_at = %s WHERE id IN ({placeholders})",
                    (datetime.now(), *data['notif_ids'])
                )
            else:
                stats['emails_failed'] += 1

        conn.commit()
        logger.info(f"Notification stats: {stats}")

    except Exception as e:
        logger.error(f"Error in send_notifications: {e}")
        conn.rollback()
    finally:
        cursor.close()
        conn.close()

    return stats


if __name__ == '__main__':
    logging.basicConfig(level=logging.INFO, format='%(asctime)s [%(levelname)s] %(message)s')
    # Test: send notifications for all unsent
    try:
        conn   = get_db_connection()
        cursor = conn.cursor(dictionary=True)
        cursor.execute('SELECT DISTINCT circular_id FROM notifications WHERE is_sent = 0 LIMIT 20')
        ids = [row['circular_id'] for row in cursor.fetchall()]
        cursor.close()
        conn.close()
        if ids:
            stats = send_notifications(ids)
            print(f"Stats: {stats}")
        else:
            print("No pending notifications.")
    except Exception as e:
        print(f"Error: {e}")
