#!/usr/bin/env python3
"""
Clean a WordPress WXR export by converting Stackable blocks into plain text
while keeping all other Gutenberg markup intact.

Usage:
    python3 clean_wxr.py source.xml output.xml
"""
import html
import re
import sys
from html.parser import HTMLParser
from pathlib import Path


class TextExtractor(HTMLParser):
    """Basic HTML to text converter (keeps only textual content)."""

    def __init__(self):
        super().__init__()
        self._parts = []

    def handle_data(self, data):
        if data and data.strip():
            self._parts.append(data.strip())

    def get_text(self):
        return ' '.join(self._parts).strip()


STACKABLE_PATTERN = re.compile(
    r'<!--\s*wp:stackable[\s\S]*?<!--\s*/wp:stackable[\s\S]*?-->',
    re.IGNORECASE
)


def strip_to_plain_text(html_fragment: str) -> str:
    parser = TextExtractor()
    parser.feed(html_fragment)
    parser.close()
    return parser.get_text()


def replace_stackable(match: re.Match) -> str:
    block = match.group(0)

    inner_match = re.search(
        r'-->([\s\S]*?)<!--\s*/wp:stackable',
        block,
        re.IGNORECASE
    )
    if not inner_match:
        return ''

    inner_html = inner_match.group(1)
    plain_text = strip_to_plain_text(inner_html)
    if not plain_text:
        return ''

    return f'<p>{html.escape(plain_text)}</p>'


def main():
    if len(sys.argv) != 3:
        print('Usage: python3 clean_wxr.py <source.xml> <output.xml>')
        sys.exit(1)

    src = Path(sys.argv[1])
    dst = Path(sys.argv[2])

    if not src.exists():
        print(f'Error: {src} not found.')
        sys.exit(1)

    raw = src.read_text(encoding='utf-8')
    cleaned = STACKABLE_PATTERN.sub(replace_stackable, raw)
    dst.write_text(cleaned, encoding='utf-8')
    print(f'Cleaned export written to {dst}')


if __name__ == '__main__':
    main()
