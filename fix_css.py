import re

with open('resources/views/livewire/storefront/hero-search.blade.php', 'r') as f:
    content = f.read()

# We need to remove the CSS between /* 1. RESET & BASE */ and /* 2. BUTTONS */
content = re.sub(r'/\* =+\n\s*1\. RESET & BASE\n\s*=+\s*\*/.*?/\* =+\n\s*2\. BUTTONS', '/* ============================================================\n   2. BUTTONS', content, flags=re.DOTALL)

# We need to remove the CSS between /* 3. HEADER */ and /* 4. HERO */
content = re.sub(r'/\* =+\n\s*3\. HEADER\n\s*=+\s*\*/.*?/\* =+\n\s*4\. HERO', '/* ============================================================\n   4. HERO', content, flags=re.DOTALL)

with open('resources/views/livewire/storefront/hero-search.blade.php', 'w') as f:
    f.write(content)
