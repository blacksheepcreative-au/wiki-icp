#!/usr/bin/env python3
"""
Convert Stackable column blocks inside a WXR export into core Gutenberg
`columns` + `column` blocks so the markup can be imported without the Stackable
plugin.

Usage:
    python3 clean_stackable_columns.py source.xml output.xml
"""
import re
import sys
from pathlib import Path


STACKABLE_COLUMN_REGEX = re.compile(
    r'<!--\s*wp:stackable/column\b.*?-->([\s\S]*?)<!--\s*/wp:stackable/column\s*-->',
    re.IGNORECASE
)

STACKABLE_COLUMNS_REGEX = re.compile(
    r'<!--\s*wp:stackable/columns\b.*?-->([\s\S]*?)<!--\s*/wp:stackable/columns\s*-->',
    re.IGNORECASE
)

STYLE_REGEX = re.compile(
    r'<style>[\s\S]*?stk-[\s\S]*?</style>',
    re.IGNORECASE
)


def convert_column(match: re.Match) -> str:
    inner = match.group(1)
    return f'<!-- wp:column -->{inner}<!-- /wp:column -->'


def convert_columns(match: re.Match) -> str:
    inner = match.group(1)
    return f'<!-- wp:columns -->{inner}<!-- /wp:columns -->'


def main():
    if len(sys.argv) != 3:
        print('Usage: python3 clean_stackable_columns.py <source.xml> <output.xml>')
        sys.exit(1)

    src = Path(sys.argv[1])
    dst = Path(sys.argv[2])

    if not src.exists():
        print(f'Error: {src} not found.')
        sys.exit(1)

    content = src.read_text(encoding='utf-8')
    content = STACKABLE_COLUMN_REGEX.sub(convert_column, content)
    content = STACKABLE_COLUMNS_REGEX.sub(convert_columns, content)
    content = content.replace('wp-block-stackable-columns', 'wp-block-columns')
    content = content.replace('wp-block-stackable-column', 'wp-block-column')
    content = STYLE_REGEX.sub('', content)
    content = re.sub(r'\sdata-block-id="[^"]+"', '', content)
    content = re.sub(r'stk-row', 'wp-block-columns__inner', content)
    content = re.sub(r'stk-block-column__content', 'wp-block-column__content', content)
    content = re.sub(r'stk-block-content', 'wp-block-content', content)

    dst.write_text(content, encoding='utf-8')
    print(f'Converted export written to {dst}')


if __name__ == '__main__':
    main()
