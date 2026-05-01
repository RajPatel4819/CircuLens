#!/usr/bin/env python3
"""
CircuLens NLP Analyzer
Classifies circulars into categories using keyword matching + optional spaCy NLP.
"""

import re
import logging
from typing import Optional

logger = logging.getLogger(__name__)

# Category keyword mappings (order matters - first match wins for primary keywords)
CATEGORY_KEYWORDS: dict[str, list[str]] = {
    'examination': [
        'exam', 'examination', 'result', 'grade', 'marksheet', 'mark sheet',
        'answer key', 'admit card', 'hall ticket', 're-exam', 'revaluation',
        'winter exam', 'summer exam', 'remedial', 'back paper', 'atkt',
    ],
    'placement': [
        'placement', 'campus drive', 'campus recruitment', 'job', 'internship',
        'tcs', 'infosys', 'wipro', 'hcl', 'accenture', 'capgemini', 'cognizant',
        'interview', 'company visit', 'pre-placement', 'recruit',
    ],
    'events': [
        'event', 'fest', 'festival', 'competition', 'hackathon', 'seminar',
        'workshop', 'conference', 'symposium', 'tech fest', 'cultural', 'sports',
        'annual day', 'farewell', 'inauguration', 'webinar', 'register',
    ],
    'timetable': [
        'timetable', 'time table', 'schedule', 'lecture', 'revised schedule',
        'class schedule', 'session', 'slot',
    ],
    'academic': [
        'academic calendar', 'academic year', 'holiday', 'syllabus', 'curriculum',
        'admission', 'enrollment', 'course', 'semester', 'fee', 'scholarship',
        'attendance', 'leave', 'vacation', 'guidelines', 'regulation', 'circular',
        'notification', 'notice', 'instruction',
    ],
}

# Fallback type
DEFAULT_TYPE = 'general'


def preprocess_text(text: str) -> str:
    """Normalize text for matching."""
    text = text.lower()
    text = re.sub(r'[^\w\s]', ' ', text)
    text = re.sub(r'\s+', ' ', text)
    return text.strip()


def keyword_classify(title: str, description: str = '') -> str:
    """Classify circular type using keyword matching."""
    combined = preprocess_text(f"{title} {description}")

    # Score each category
    scores: dict[str, int] = {cat: 0 for cat in CATEGORY_KEYWORDS}

    for category, keywords in CATEGORY_KEYWORDS.items():
        for kw in keywords:
            kw_lower = kw.lower()
            if kw_lower in combined:
                # Exact word boundary match gets higher score
                if re.search(r'\b' + re.escape(kw_lower) + r'\b', combined):
                    scores[category] += 2
                else:
                    scores[category] += 1

    best_category = max(scores, key=lambda c: scores[c])
    if scores[best_category] == 0:
        return DEFAULT_TYPE

    return best_category


def spacy_classify(title: str, description: str = '') -> Optional[str]:
    """
    Classify using spaCy NER and POS tagging for better accuracy.
    Falls back to None if spaCy is not available.
    """
    try:
        import spacy

        # Load model (use small English model)
        try:
            nlp = spacy.load('en_core_web_sm')
        except OSError:
            logger.debug("spaCy model 'en_core_web_sm' not found. Falling back to keyword matching.")
            return None

        text = f"{title}. {description}"[:1000]
        doc  = nlp(text)

        # Extract named entities and noun chunks
        entities  = {ent.label_: ent.text.lower() for ent in doc.ents}
        noun_text = ' '.join(chunk.text.lower() for chunk in doc.noun_chunks)
        full_text = f"{text.lower()} {noun_text}"

        # Entity-based hints
        if 'ORG' in entities:
            org = entities['ORG']
            placement_orgs = ['tcs', 'infosys', 'wipro', 'accenture', 'capgemini']
            if any(po in org for po in placement_orgs):
                return 'placement'

        # Re-run keyword matching on enriched text
        return keyword_classify(full_text, '')

    except ImportError:
        logger.debug("spaCy not installed. Using keyword matching only.")
        return None
    except Exception as e:
        logger.debug(f"spaCy classification failed: {e}")
        return None


def classify_circular(title: str, description: str = '') -> str:
    """
    Main classification function.
    Tries spaCy first, falls back to keyword matching.
    """
    if not title:
        return DEFAULT_TYPE

    # Try spaCy
    result = spacy_classify(title, description)
    if result:
        return result

    # Fallback to keyword matching
    return keyword_classify(title, description)


def extract_keywords(text: str, top_n: int = 10) -> list[str]:
    """Extract top keywords from text (simple frequency-based)."""
    # Remove common stopwords
    stopwords = {
        'the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for',
        'of', 'with', 'by', 'from', 'is', 'are', 'was', 'were', 'be', 'been',
        'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'can',
        'could', 'should', 'may', 'might', 'shall', 'this', 'that', 'these',
        'those', 'it', 'its', 'all', 'any', 'as', 'if', 'not', 'no', 'so',
    }

    words = re.findall(r'\b[a-zA-Z]{3,}\b', text.lower())
    words = [w for w in words if w not in stopwords]

    freq: dict[str, int] = {}
    for w in words:
        freq[w] = freq.get(w, 0) + 1

    return sorted(freq, key=lambda w: freq[w], reverse=True)[:top_n]


if __name__ == '__main__':
    # Test classification
    test_cases = [
        ("Winter Examination 2024 Schedule", "Exam schedule for all BE/BTech students"),
        ("Campus Placement Drive - TCS", "TCS recruitment drive for final year students"),
        ("Annual Tech Fest TechVision 2024", "Register your teams for various competitions"),
        ("Academic Calendar 2024-25", "Important dates for the academic year"),
        ("Revised Timetable Odd Semester", "Changes in lecture scheduling"),
        ("Scholarship Application Deadline", "Apply for merit scholarships"),
    ]

    for title, desc in test_cases:
        result = classify_circular(title, desc)
        print(f"[{result:12}] {title}")
