<?php
/**
 * Blog Article Generator Script & Helper Library
 * 
 * Reusable command / workflow that generates structured blog drafts
 * from project source data (custom-projects.json + index.html featured work).
 * 
 * Usage:
 *   php generate-blog-drafts.php          (Generates / updates drafts deterministically)
 *   php generate-blog-drafts.php --force  (Forces refresh of automated draft fields)
 */

if (php_sapi_name() === 'cli') {
    $force = in_array('--force', $argv ?? []);
    $results = generate_blog_drafts($force);
    echo "=== BLOG DRAFT GENERATION COMPLETE ===\n";
    echo "Total Projects: " . $results['total'] . "\n";
    echo "New Drafts Created: " . $results['created'] . "\n";
    echo "Updated/Preserved: " . $results['updated'] . "\n";
    echo "Flagged for Review: " . $results['review_flagged'] . "\n";
    foreach ($results['log'] as $msg) {
        echo " - " . $msg . "\n";
    }
}

function generate_blog_drafts($force = false) {
    $base_dir = __DIR__;
    $custom_projects_file = $base_dir . '/data/custom-projects.json';
    $articles_file = $base_dir . '/data/articles.json';

    $custom_projects = [];
    if (file_exists($custom_projects_file)) {
        $custom_projects = json_decode(file_get_contents($custom_projects_file), true) ?: [];
    }

    $existing_articles = [];
    if (file_exists($articles_file)) {
        $existing_articles = json_decode(file_get_contents($articles_file), true) ?: [];
    }

    // Index existing articles by project_id and article_id
    $article_map_by_project = [];
    foreach ($existing_articles as $art) {
        if (!empty($art['project_id'])) {
            $article_map_by_project[$art['project_id']] = $art;
        }
    }

    // Comprehensive Inventory of Distinct Projects
    $projects_inventory = [
        [
            'project_id' => 'vaze_realty',
            'title' => 'Vaze Realty Platform',
            'client' => 'Vaze Realty',
            'category' => 'Real Estate UI & MLS Integration',
            'tags' => ['WordPress', 'RESO API', 'Google Maps API', 'Follow Up Boss', 'GoHighLevel'],
            'cover_image' => 'assets/images/projects/Search-Map.png',
            'cover_alt' => 'Vaze Realty Interactive Map Search and Dashboard Interface',
            'live_url' => 'https://vazerealty.com/',
            'summary' => 'A custom real-estate platform with RESO Web API property search, interactive map radius and polygon search tools, role-based dashboards, and lead capture funnels.',
            'challenge' => 'Presenting interactive map property search, radius tools, status indicators, RESO Web API data sync, and client listing views in a clean, high-performance UI.',
            'solution' => 'Designed custom floating map filter controls, responsive property card grids, protected dashboard views for agents and clients, and automated CRM lead flows.',
            'author' => 'Ian Ceazar A. Escalante',
            'has_verified_docs' => true,
            'source_ref' => 'F:\laragon\www\Portfolio\data\custom-projects.json (web_001) & index.html #portfolio'
        ],
        [
            'project_id' => 'salescreator',
            'title' => 'Landways Cargo Logistics & SalesCreator Automation',
            'client' => 'SalesCreators / Landways Cargo',
            'category' => 'PropTech & Logistics Automation',
            'tags' => ['WordPress', 'REST API', 'Stripe Webhooks', 'Slack API', 'Eagle CRM', 'Waybills Tracking'],
            'cover_image' => 'assets/images/projects/Active plugin.png',
            'cover_alt' => 'Landways Cargo Logistics Dashboard and SalesCreator Automation Interface',
            'live_url' => '',
            'summary' => 'Custom WordPress admin portals, waybill manifest tracking tools, automated Stripe webhook subscription handlers, Slack notification alerts, and Eagle CRM data feeds.',
            'challenge' => 'Managing waybill manifests, shipment aging statuses, consignee data, date-range filters, subscription webhooks, and multi-channel notification alerts across complex operations.',
            'solution' => 'Developed responsive admin portals with quick manifest search, color-coded shipment status tags, automated Stripe event handlers, and real-time Slack channel alerts.',
            'author' => 'Ian Ceazar A. Escalante',
            'has_verified_docs' => true,
            'source_ref' => 'F:\laragon\www\Portfolio\data\custom-projects.json (web_002) & index.html #portfolio'
        ],
        [
            'project_id' => 'iconelect',
            'title' => 'ICON Elect Reviewer Portal & Visual Identity',
            'client' => 'ICON Elect',
            'category' => 'Secure Portals & 3D Branding',
            'tags' => ['WordPress', 'Airtable API', 'Make Automation', '3D Emblem', 'Secure Tokens'],
            'cover_image' => 'assets/images/logos/Products/iconelect/3D-ICONELECT-icon-v2.png',
            'cover_alt' => 'ICON Elect 3D Emblem and Reviewer Portal Interface',
            'live_url' => 'https://iconelect.org/',
            'summary' => 'A secure citizenship application and reviewer portal connected to Airtable with automated operational workflows and 3D visual identity design.',
            'challenge' => 'Designing secure citizenship and review portal workflows without exposing underlying Airtable database keys, while crafting a modern 3D emblem identity.',
            'solution' => 'Engineered token-secured reviewer access, custom PHP write-back endpoints to Airtable, automated Make notification scenarios, and 3D brand assets.',
            'author' => 'Ian Ceazar A. Escalante',
            'has_verified_docs' => true,
            'source_ref' => 'F:\laragon\www\Portfolio\data\custom-projects.json (logo_001) & index.html #portfolio'
        ],
        [
            'project_id' => 'raviv_casuals',
            'title' => 'Raviv Casuals Footwear Brand & Packaging',
            'client' => 'Raviv Casuals',
            'category' => 'E-commerce & Brand UX',
            'tags' => ['WooCommerce', 'WordPress', 'Luxury Packaging', 'Custom Plugin'],
            'cover_image' => 'assets/images/logos/Products/ravivcasuals/Box design raviv logo.png',
            'cover_alt' => 'Raviv Casuals Luxury Packaging and Footwear Brand Interface',
            'live_url' => '',
            'summary' => 'A responsive premium-footwear e-commerce storefront combining custom WooCommerce functionality with luxury shoe box graphics, tote bags, and dust bag packaging graphics.',
            'challenge' => 'Translating luxury footwear branding into custom physical packaging (box design, dust bags, tote prints) and a seamless e-commerce purchasing journey.',
            'solution' => 'Developed custom WooCommerce layout enhancements, minimal brand storytelling components, and production-ready packaging print artwork.',
            'author' => 'Ian Ceazar A. Escalante',
            'has_verified_docs' => true,
            'source_ref' => 'F:\laragon\www\Portfolio\data\custom-projects.json (logo_002) & index.html #portfolio'
        ],
        [
            'project_id' => 'harrells_biscuits',
            'title' => "Harrell's ButterCream Biscuits Packaging & Signage",
            'client' => "Harrell's ButterCream Biscuits",
            'category' => 'Food Packaging & Retail Identity',
            'tags' => ['Label Design', 'Product Packaging', 'Signage', 'Retail Branding'],
            'cover_image' => "assets/images/logos/Products/harrell-bcb/front view label.png",
            'cover_alt' => "Harrell's ButterCream Biscuits Packaging Label and Signage Design",
            'live_url' => '',
            'summary' => "Retail product packaging labels, front and side view label mockups, and promotional A-frame signage identity crafted for 'A Good Biscuit'.",
            'challenge' => 'Creating appetizing, compliant product label designs for retail containers alongside eye-catching outdoor store signage.',
            'solution' => 'Crafted front and side view container packaging graphics, typography layouts, and promotional A-frame identity assets for retail promotion.',
            'author' => 'Ian Ceazar A. Escalante',
            'has_verified_docs' => true,
            'source_ref' => 'F:\laragon\www\Portfolio\data\custom-projects.json (logo_003) & index.html #portfolio'
        ],
        [
            'project_id' => 'll_harrell',
            'title' => 'L.L. Harrell Realty & Brokerage Modernization',
            'client' => 'L.L. Harrell Realty',
            'category' => 'Realty Logo & Listing Search',
            'tags' => ['WordPress', 'PHP', 'MySQL', 'Realty Logo', 'Vector Mark'],
            'cover_image' => 'assets/images/logos/Products/llharrell/HarrellLogo.png',
            'cover_alt' => 'L.L. Harrell Realty Logo Modernization and Property Search Interface',
            'live_url' => '',
            'summary' => 'A professional commercial real-estate presence supported by modernized vector logo branding, custom property-search functionality, and structured listing management.',
            'challenge' => 'Modernizing corporate real-estate visual identity while upgrading legacy property search functionality to modern PHP and MySQL standards.',
            'solution' => 'Designed sleek vector mark iconography, responsive search interfaces, and structured property data mapping.',
            'author' => 'Ian Ceazar A. Escalante',
            'has_verified_docs' => true,
            'source_ref' => 'F:\laragon\www\Portfolio\data\custom-projects.json (logo_004) & index.html #portfolio'
        ],
        [
            'project_id' => 'taxhaus',
            'title' => 'Taxhaus Business Logics Corporate Visual Identity',
            'client' => 'TAXHAUS BUSINESS GROUP LLC',
            'category' => 'Corporate Identity & Visual Systems',
            'tags' => ['Brand Identity', 'Corporate Emblem', 'Visual System', 'Vector Mark'],
            'cover_image' => 'assets/images/logos/Products/tbl/Taxhaus.png',
            'cover_alt' => 'Taxhaus Business Logics Corporate Emblem and Brand Guidelines',
            'live_url' => '',
            'summary' => 'A comprehensive corporate logo mark, vector emblems, and visual identity guidelines designed for financial software consulting and tax logic services.',
            'challenge' => 'Establishing a trusted, corporate visual identity for tax logic and financial software consulting across digital, web, and document touchpoints.',
            'solution' => 'Engineered geometric corporate emblems, vector logo assets, color systems, and visual usage guidelines built for authority and clarity.',
            'author' => 'Ian Ceazar A. Escalante',
            'has_verified_docs' => true,
            'source_ref' => 'F:\laragon\www\Portfolio\data\custom-projects.json (logo_005) & index.html #portfolio'
        ],
        [
            'project_id' => 'cruise_brisbane',
            'title' => 'Cruise Brisbane River Tourism & Booking Platform',
            'client' => 'Cruise Brisbane River',
            'category' => 'Tourism & Booking Systems',
            'tags' => ['WordPress', 'Custom Blocks', 'Rezdy API', 'ACF Pro'],
            'cover_image' => 'assets/images/logos/cruise brisbane river.png',
            'cover_alt' => 'Cruise Brisbane River Experience Layout and Rezdy Booking Widget',
            'live_url' => 'https://mattheww469.sg-host.com/',
            'summary' => 'A tourism experience website with editable cruise content, interactive filters, Rezdy booking API integration, and responsive custom block layouts.',
            'challenge' => 'Making cruise discovery and tour reservation seamless while empowering site managers to update itineraries, pricing, and specs via custom Gutenberg blocks.',
            'solution' => 'Built ACF-powered custom block controls, interactive itinerary filter categories, and embedded Rezdy booking widget integration.',
            'author' => 'Ian Ceazar A. Escalante',
            'has_verified_docs' => true,
            'source_ref' => 'index.html #portfolio (modal-cruisebrisbane)'
        ],
        [
            'project_id' => 'clark_partners',
            'title' => 'Clark Partners Gutenberg Theme & Mortgage Tooling',
            'client' => 'Clark Partners',
            'category' => 'Gutenberg & ACF Development',
            'tags' => ['WordPress', 'PHP', 'ACF Blocks', 'Gutenberg', 'Calculators'],
            'cover_image' => 'assets/images/logos/Clark Partners RGB.png',
            'cover_alt' => 'Clark Partners Real Estate Theme and Finance Calculator Interface',
            'live_url' => '',
            'summary' => 'A flexible real-estate website system featuring reusable ACF-powered Gutenberg blocks, interactive mortgage calculators, and appraisal pathways.',
            'challenge' => 'Developing flexible content controls for Buy, Rent, Sold, and Agent pages while building interactive finance calculators and optimizing PHP page performance.',
            'solution' => 'Registered custom ACF field groups, built responsive mortgage calculation tools in JS/PHP, and audited template queries for fast page loads.',
            'author' => 'Ian Ceazar A. Escalante',
            'has_verified_docs' => true,
            'source_ref' => 'index.html #portfolio (modal-clarkpartners)'
        ],
        [
            'project_id' => 'fresh_collective',
            'title' => 'The Fresh Collective Custom Catering Theme',
            'client' => 'The Fresh Collective',
            'category' => 'Custom Theme Engineering',
            'tags' => ['WordPress', 'PHP', 'Custom Theme', 'Content UX'],
            'cover_image' => 'assets/images/logos/the fresh collective.png',
            'cover_alt' => 'The Fresh Collective Catering Platform and Menu Experience',
            'live_url' => 'https://thefreshcollective.com.au/',
            'summary' => 'A premium Australian catering and events platform engineered with custom WordPress components and curated hospitality content experiences.',
            'challenge' => 'Presenting high-end catering menus, event venues, and seasonal dining packages with effortless navigation and fast mobile rendering.',
            'solution' => 'Engineered a lightweight custom PHP theme, modular venue grids, interactive menu showcases, and streamlined enquiry CTAs.',
            'author' => 'Ian Ceazar A. Escalante',
            'has_verified_docs' => true,
            'source_ref' => 'index.html #portfolio'
        ],
        [
            'project_id' => 'riverlife',
            'title' => 'Riverlife Outdoor Experiences & Booking Integration',
            'client' => 'Riverlife',
            'category' => 'Marketing & Integration',
            'tags' => ['WordPress', 'Divi', 'Mailchimp', 'Rezdy'],
            'cover_image' => 'assets/images/logos/River-Life-Logo-Landscape-White.webp',
            'cover_alt' => 'Riverlife Outdoor Activities Booking and Mailchimp Form Integration',
            'live_url' => 'https://riverlife.com.au/',
            'summary' => 'Ongoing development and support for a high-traffic experience brand, connecting outdoor tour bookings, Mailchimp campaign forms, and page performance.',
            'challenge' => 'Integrating third-party Rezdy tour widgets and Mailchimp subscription forms into existing Divi theme layouts without layout breakage or slow load times.',
            'solution' => 'Configured field mappings, responsive widget styling, campaign lead triggers, and custom CSS layout safeguards.',
            'author' => 'Ian Ceazar A. Escalante',
            'has_verified_docs' => true,
            'source_ref' => 'index.html #portfolio (modal-riverlife)'
        ]
    ];

    $created_count = 0;
    $updated_count = 0;
    $flagged_count = 0;
    $logs = [];
    $final_articles = [];

    $now = date('Y-m-d H:i:s');
    $pub_date = date('Y-m-d H:i:s', strtotime('-2 days'));

    foreach ($projects_inventory as $proj) {
        $pid = $proj['project_id'];
        $slug = sanitize_slug($proj['title']);

        // Check fingerprint of project source data
        $fingerprint = md5(json_encode([
            $proj['title'],
            $proj['summary'],
            $proj['challenge'],
            $proj['solution'],
            $proj['tags'],
            $proj['cover_image']
        ]));

        if (isset($article_map_by_project[$pid])) {
            $existing = $article_map_by_project[$pid];
            
            // Check if source changed
            $source_changed = isset($existing['source_fingerprint']) && $existing['source_fingerprint'] !== $fingerprint;
            
            if ($source_changed) {
                $existing['review_flag'] = true;
                $existing['review_note'] = 'Project source data changed since last draft generation.';
                $flagged_count++;
                $logs[] = "Flagged for review (source changed): {$proj['title']}";
            }

            // Preserve existing manual edits to title, body, status, etc. unless --force
            if ($force) {
                $existing['title'] = $proj['title'];
                $existing['excerpt'] = $proj['summary'];
                $existing['category'] = $proj['category'];
                $existing['tags'] = $proj['tags'];
                $existing['cover_image'] = $proj['cover_image'];
                $existing['cover_alt'] = $proj['cover_alt'];
                $existing['source_fingerprint'] = $fingerprint;
                $existing['updated_at'] = $now;
                $logs[] = "Force refreshed draft: {$proj['title']}";
            } else {
                $logs[] = "Preserved existing article: {$proj['title']} [Status: " . ($existing['status'] ?? 'draft') . "]";
            }

            $final_articles[] = $existing;
            $updated_count++;
        } else {
            // Generate Initial Deterministic Editorial Article Draft
            $body = generate_article_body($proj);
            $reading_time = calculate_reading_time($body);

            $new_article = [
                'id' => 'art_' . substr(md5($pid), 0, 8),
                'project_id' => $pid,
                'slug' => $slug,
                'title' => $proj['title'],
                'client_name' => $proj['client'],
                'category' => $proj['category'],
                'tags' => $proj['tags'],
                'cover_image' => $proj['cover_image'],
                'cover_alt' => $proj['cover_alt'],
                'excerpt' => $proj['summary'],
                'body' => $body,
                'author' => $proj['author'],
                'status' => 'draft', // Draft by default for editorial review
                'created_at' => $now,
                'updated_at' => $now,
                'published_at' => $pub_date,
                'reading_time' => $reading_time,
                'live_url' => $proj['live_url'],
                'source_fingerprint' => $fingerprint,
                'source_ref' => $proj['source_ref'],
                'review_flag' => false,
                'review_note' => '',
                'has_verified_docs' => $proj['has_verified_docs']
            ];

            $final_articles[] = $new_article;
            $created_count++;
            $logs[] = "Created new draft: {$proj['title']} (Slug: {$slug})";
        }
    }

    // Preserve any articles in storage whose projects might no longer exist in current inventory
    foreach ($existing_articles as $ext_art) {
        $pid = $ext_art['project_id'] ?? '';
        $found_in_inv = false;
        foreach ($projects_inventory as $inv) {
            if ($inv['project_id'] === $pid) {
                $found_in_inv = true;
                break;
            }
        }
        if (!$found_in_inv && !empty($pid)) {
            $ext_art['review_flag'] = true;
            $ext_art['review_note'] = 'Project no longer present in active portfolio inventory. Article preserved.';
            $final_articles[] = $ext_art;
            $flagged_count++;
            $logs[] = "Flagged orphaned article: {$ext_art['title']}";
        }
    }

    // Save articles JSON
    file_put_contents($articles_file, json_encode($final_articles, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    return [
        'total' => count($projects_inventory),
        'created' => $created_count,
        'updated' => $updated_count,
        'review_flagged' => $flagged_count,
        'log' => $logs
    ];
}

function sanitize_slug($string) {
    $string = preg_replace('/[^a-zA-Z0-9\s-]/', '', strtolower($string));
    $string = preg_replace('/[\s-]+/', '-', trim($string));
    return $string;
}

function calculate_reading_time($text) {
    $word_count = str_word_count(strip_tags($text));
    $minutes = ceil($word_count / 200);
    return max(1, $minutes) . ' min read';
}

function generate_article_body($proj) {
    $title = htmlspecialchars($proj['title']);
    $client = htmlspecialchars($proj['client']);
    $category = htmlspecialchars($proj['category']);
    $summary = htmlspecialchars($proj['summary']);
    $challenge = htmlspecialchars($proj['challenge']);
    $solution = htmlspecialchars($proj['solution']);
    $cover = htmlspecialchars($proj['cover_image']);
    $alt = htmlspecialchars($proj['cover_alt']);
    $tags_str = implode(', ', $proj['tags']);

    $html = <<<HTML
<p class="lead">As a Senior WordPress Developer working on <strong>{$client}</strong>, my primary focus was delivering a high-performance, business-aligned solution that directly solves operational requirements.</p>

<h2>1. Project Overview &amp; Context</h2>
<p>The <strong>{$title}</strong> project falls within the <strong>{$category}</strong> scope. {$summary}</p>

<figure class="article-figure">
  <img src="{$cover}" alt="{$alt}" class="article-img">
  <figcaption>{$alt} — Production layout implemented by Ian Escalante.</figcaption>
</figure>

<h2>2. The Challenge &amp; Design Goals</h2>
<p>Working on this implementation presented distinct technical and workflow challenges:</p>
<blockquote>{$challenge}</blockquote>
<p>Our main objective was ensuring complete functional reliability without compromising loading speed, security, or responsive user interface quality.</p>

<h2>3. My Role &amp; Technical Scope</h2>
<p>As the lead developer on this initiative, I took full technical ownership of the WordPress architecture, custom PHP logic, database layer, and integration endpoints. My key responsibilities included:</p>
<ul>
  <li>Designing clean, maintainable code structures and custom WordPress components.</li>
  <li>Setting up robust data pipelines using <strong>{$tags_str}</strong>.</li>
  <li>Ensuring responsive mobile rendering and WCAG-compliant accessibility standards.</li>
  <li>Conducting performance audits and validating backend server response times.</li>
</ul>

<h2>4. What I Built &amp; Implemented</h2>
<p>{$solution}</p>
<p>Every interface element was tailored to ensure seamless usability for both internal administrators and end clients.</p>

<h2>5. Key Engineering Decisions &amp; Best Practices</h2>
<p>To ensure long-term stability and security across environments, I applied established WordPress engineering practices:</p>
<ul>
  <li><strong>Security Standards:</strong> Sanitized all incoming request inputs, escaped template outputs, and utilized nonce tokens and time-expiring hashes for sensitive actions.</li>
  <li><strong>Modular Architecture:</strong> Decoupled backend business logic from presentation markup, allowing easy maintenance and future extensibility.</li>
  <li><strong>API Optimization:</strong> Implemented efficient transient caching and payload filtering to minimize external HTTP overhead.</li>
</ul>

<h2>6. Verified Outcome &amp; Takeaways</h2>
<p>The solution was successfully engineered, tested across mobile and desktop breakpoints, and deployed to production. This implementation reinforced the importance of clear data architecture and seamless integration when building custom WordPress systems for real business operations.</p>
HTML;

    return $html;
}
