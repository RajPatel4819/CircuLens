#!/usr/bin/env python3
"""
CircuLens Matcher
Matches new circulars to users based on their preferences and creates notifications.
"""

import json
import logging
import os
from typing import Optional

from dotenv import load_dotenv

load_dotenv()

logger = logging.getLogger(__name__)

DB_HOST = os.getenv('DB_HOST', 'localhost')
DB_NAME = os.getenv('DB_NAME', 'circulens')
DB_USER = os.getenv('DB_USER', 'root')
DB_PASS = os.getenv('DB_PASS', '')


def get_db_connection():
    """Get MySQL database connection."""
    import mysql.connector
    return mysql.connector.connect(
        host=DB_HOST,
        database=DB_NAME,
        user=DB_USER,
        password=DB_PASS,
        charset='utf8mb4',
    )


def get_circular(cursor, circular_id: int) -> Optional[dict]:
    """Fetch a circular by ID."""
    cursor.execute(
        'SELECT id, title, description, circular_type FROM circulars WHERE id = %s AND is_active = 1 LIMIT 1',
        (circular_id,)
    )
    return cursor.fetchone()


def get_all_users_with_preferences(cursor) -> list[dict]:
    """Fetch all active users with their preferences."""
    cursor.execute(
        '''SELECT u.id AS user_id, u.name, u.email,
                  p.degree, p.department, p.semester, p.circular_types
           FROM users u
           LEFT JOIN preferences p ON p.user_id = u.id
           WHERE u.is_active = 1'''
    )
    users = cursor.fetchall()

    for user in users:
        if user.get('circular_types'):
            try:
                user['circular_types'] = json.loads(user['circular_types'])
            except (json.JSONDecodeError, TypeError):
                user['circular_types'] = []
        else:
            user['circular_types'] = []

    return users


def notification_exists(cursor, user_id: int, circular_id: int) -> bool:
    """Check if a notification already exists for this user+circular pair."""
    cursor.execute(
        'SELECT id FROM notifications WHERE user_id = %s AND circular_id = %s LIMIT 1',
        (user_id, circular_id)
    )
    return cursor.fetchone() is not None


def user_matches_circular(user: dict, circular: dict) -> bool:
    """
    Determine whether a circular is relevant to a user.
    Returns True if the user should receive notification for this circular.
    """
    pref_types = user.get('circular_types', [])

    # If user has no type preferences, show all circulars
    if not pref_types:
        return True

    # Check if circular type matches any of user's preferred types
    return circular.get('circular_type') in pref_types


def create_notifications_for_circular(circular_id: int) -> int:
    """
    Match a circular to all relevant users and create notification records.
    Returns number of notifications created.
    """
    try:
        conn   = get_db_connection()
        cursor = conn.cursor(dictionary=True)
    except Exception as e:
        logger.error(f"DB connection failed: {e}")
        return 0

    created = 0
    try:
        circular = get_circular(cursor, circular_id)
        if not circular:
            logger.warning(f"Circular {circular_id} not found or inactive.")
            return 0

        logger.info(f"Matching circular [{circular['circular_type']}]: {circular['title'][:60]}")

        users = get_all_users_with_preferences(cursor)
        logger.info(f"Checking {len(users)} users for matches")

        for user in users:
            if not user_matches_circular(user, circular):
                continue

            if notification_exists(cursor, user['user_id'], circular_id):
                continue

            cursor.execute(
                'INSERT INTO notifications (user_id, circular_id, is_sent, is_read) VALUES (%s, %s, 0, 0)',
                (user['user_id'], circular_id)
            )
            created += 1

        conn.commit()
        logger.info(f"Created {created} notifications for circular {circular_id}")

    except Exception as e:
        logger.error(f"Error creating notifications: {e}")
        conn.rollback()
    finally:
        cursor.close()
        conn.close()

    return created


def match_all_new_circulars() -> int:
    """
    Match all unmatched circulars to users.
    Useful for initial setup or when running manually.
    """
    try:
        conn   = get_db_connection()
        cursor = conn.cursor(dictionary=True)
    except Exception as e:
        logger.error(f"DB connection failed: {e}")
        return 0

    total = 0
    try:
        # Find circulars that have no notifications yet
        cursor.execute(
            '''SELECT c.id FROM circulars c
               WHERE c.is_active = 1
               AND NOT EXISTS (SELECT 1 FROM notifications n WHERE n.circular_id = c.id)
               ORDER BY c.created_at DESC
               LIMIT 100'''
        )
        unmatched = [row['id'] for row in cursor.fetchall()]
        logger.info(f"Found {len(unmatched)} circulars without notifications")
    except Exception as e:
        logger.error(f"Error fetching unmatched circulars: {e}")
        return 0
    finally:
        cursor.close()
        conn.close()

    for cid in unmatched:
        total += create_notifications_for_circular(cid)

    return total


if __name__ == '__main__':
    logging.basicConfig(level=logging.INFO, format='%(asctime)s [%(levelname)s] %(message)s')
    count = match_all_new_circulars()
    print(f"Total notifications created: {count}")
