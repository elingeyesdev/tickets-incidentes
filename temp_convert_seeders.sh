#!/bin/bash

# Script to convert Announcement::create to Announcement::firstOrCreate

# Function to process a single file
process_file() {
    local file="$1"

    echo "Processing: $file"

    # Backup original file
    cp "$file" "$file.bak"

    # Use perl for complex multi-line replacements
    perl -i -pe 'BEGIN{undef $/;} s/Announcement::create\(\[\s*'\''id'\'' => Str::uuid\(\),\s*'\''company_id'\'' => \$company->id,\s*'\''author_id'\'' => \$admin->id,\s*'\''title'\'' => '\''([^'\'']+)'\'',/\/\/ [IDEMPOTENCY] Use firstOrCreate to prevent duplicate announcements\n        Announcement::firstOrCreate(\n            [\n                '\''company_id'\'' => \$company->id,\n                '\''title'\'' => '\''$1'\'',\n            ],\n            [\n            '\''author_id'\'' => \$admin->id,\n            '\''title'\'' => '\''$1'\'',/g' "$file"

    echo "Done processing: $file"
}

# Process each seeder file
FILES=(
    "app/Features/ContentManagement/Database/Seeders/BancoFassilAnnouncementsSeeder.php"
    "app/Features/ContentManagement/Database/Seeders/YPFBAnnouncementsSeeder.php"
    "app/Features/ContentManagement/Database/Seeders/TigoAnnouncementsSeeder.php"
    "app/Features/ContentManagement/Database/Seeders/CerveceriaBolividanaAnnouncementsSeeder.php"
)

for file in "${FILES[@]}"; do
    if [ -f "$file" ]; then
        process_file "$file"
    else
        echo "File not found: $file"
    fi
done

echo "All files processed!"
