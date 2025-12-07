#!/usr/bin/env python3
import re
import sys

def convert_create_to_first_or_create(filepath):
    """Convert Announcement::create() to firstOrCreate() in a seeder file."""

    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()

    # Pattern to match Announcement::create([...])
    # We'll use a different approach - find each create block and replace it

    # Step 1: Replace the opening of Announcement::create with firstOrCreate structure
    # This regex matches: Announcement::create([\n            'id' => Str::uuid(),\n            'company_id' => $company->id,\n            'author_id' => $admin->id,\n            'title' => 'TITLE',

    pattern = r"Announcement::create\(\[\s*'id' => Str::uuid\(\),\s*'company_id' => \$company->id,\s*'author_id' => \$admin->id,\s*'title' => '([^']+)',"

    def replacement(match):
        title = match.group(1)
        return f"""// [IDEMPOTENCY] Use firstOrCreate to prevent duplicate announcements
        Announcement::firstOrCreate(
            [
                'company_id' => $company->id,
                'title' => '{title}',
            ],
            [
            'author_id' => $admin->id,"""

    content = re.sub(pattern, replacement, content)

    # Step 2: Replace closing ]); with ]\n        );
    # But only for the announcements (need to be careful here)
    # Look for patterns like:
    #     'published_at' => '2025-XX-XX XX:XX:XX',\n        ]);
    # OR 'published_at' => null,\n        ]);

    content = re.sub(
        r"(\s*'published_at' => (?:'[^']+\'|null),)\s*\]\);",
        r"\1\n            ]\n        );",
        content
    )

    # Write back
    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)

    print(f"[OK] Converted {filepath}")

# Files to process
files = [
    r"app\Features\ContentManagement\Database\Seeders\BancoFassilAnnouncementsSeeder.php",
    r"app\Features\ContentManagement\Database\Seeders\YPFBAnnouncementsSeeder.php",
    r"app\Features\ContentManagement\Database\Seeders\TigoAnnouncementsSeeder.php",
    r"app\Features\ContentManagement\Database\Seeders\CerveceriaBolividanaAnnouncementsSeeder.php",
]

if __name__ == "__main__":
    for file in files:
        try:
            convert_create_to_first_or_create(file)
        except Exception as e:
            print(f"[ERROR] Error processing {file}: {e}")
            sys.exit(1)

    print("\n[OK] All files converted successfully!")
