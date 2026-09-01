import re

with open('tmp/homepage/index.html', 'r') as f:
    mockup = f.read()

# Extract styles
styles_match = re.search(r'<style>(.*?)</style>', mockup, re.DOTALL)
styles = styles_match.group(1) if styles_match else ''

# Remove global resets from styles to prevent bleeding
styles = re.sub(r'/\* 1\. RESET & BASE \*/.*?(?=/\* 2\. BUTTONS \*/)', '', styles, flags=re.DOTALL)
styles = re.sub(r'/\* 3\. HEADER \*/.*?(?=/\* 4\. HERO \*/)', '', styles, flags=re.DOTALL)
styles = re.sub(r'/\* 10\. FOOTER \*/.*?(?=/\* 11\. RESPONSIVE \*/)', '', styles, flags=re.DOTALL)

# Extract SVG sprite
svg_match = re.search(r'(<svg width="0" height="0".*?</svg>)', mockup, re.DOTALL)
svg = svg_match.group(1) if svg_match else ''

# Extract Hero HTML
hero_match = re.search(r'(<section class="hero" id="top">.*?</section>)', mockup, re.DOTALL)
hero = hero_match.group(1) if hero_match else ''

# Extract Sections (Who we serve, Supply chain, Value strip, CTA band)
sections_match = re.search(r'(<!-- ====\n     WHO WE SERVE.*?)(?=<!-- ====\n     FOOTER)', mockup, re.DOTALL)
sections = sections_match.group(1) if sections_match else ''

with open('resources/views/livewire/storefront/hero-search.blade.php', 'r') as f:
    current = f.read()

# Get the search bar and the rest of the file
search_match = re.search(r'(\{\{-- ═══════════════════════════════════════════════════════════\n         FLOATING SEARCH PANEL.*)', current, re.DOTALL)
rest_of_file = search_match.group(1) if search_match else ''

# Inject stats dynamic values into the hero
hero = hero.replace('<strong>7</strong>', '<strong>{{ number_format($this->stats[\'companies\']) }}+</strong>')
hero = hero.replace('<strong>100%</strong>', '<strong>{{ number_format($this->stats[\'products\']) }}+</strong>')
hero = hero.replace('<strong>B2B+B2C</strong>', '<strong>{{ $this->stats[\'categories\'] }}</strong>')
hero = hero.replace('Verified Companies</span>', 'Verified Companies</span>')
hero = hero.replace('Halal Products</span>', 'Halal Products</span>')
hero = hero.replace('Categories</span>', 'Categories</span>')

# Update image paths
hero = hero.replace('assets/', '/images/')

# Add rectangular logo
# The user wants both logos: rectangular (/images/brand-logo.png) and circular (/images/halal_store_main_logo.png)
# Let's put the rectangular logo above the headline in the hero copy
hero = hero.replace('<h1>', '<div class="mb-4"><img src="/images/brand-logo.png" alt="1HalalStore" style="height: 48px; object-fit: contain;"></div>\n      <h1>')

# Make sure buttons use correct routing
hero = hero.replace('href="#join" class="btn btn-primary"', 'href="{{ route(\'register\') }}" wire:navigate class="btn btn-primary"')

default_home_match = re.search(r'(.*?)\{\{-- ═══════════════════════════════════════════════════════════\n         DEFAULT HOME CONTENT \(when no search\).*', rest_of_file, re.DOTALL)

if default_home_match:
    search_part = default_home_match.group(1)
    new_rest = search_part + f"""{{{{-- ═══════════════════════════════════════════════════════════
         DEFAULT HOME CONTENT (when no search)
    ═══════════════════════════════════════════════════════════ --}}}}
    @else
        {sections}
    @endif
</div>
"""
else:
    new_rest = rest_of_file

new_content_final = f"""<div>
<style>
{styles}
</style>
{svg}

{hero}

{new_rest}
"""

with open('resources/views/livewire/storefront/hero-search.blade.php', 'w') as f:
    f.write(new_content_final)

print("Done.")
