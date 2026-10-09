<?php
/**
 * Improved Blog Article Generator & Relationship Mapper
 * 
 * Reusable command / workflow that generates structured blog drafts
 * from verified project data and explicit work experience relationships.
 * 
 * Generates three distinct supported article types:
 *   - project_story   (Architectural overview, role, implementation, business outcomes)
 *   - development_fix (Technical debugging, API edge cases, performance/query optimization)
 *   - design_notes    (Visual hierarchy, component UI, packaging, vector brand design)
 * 
 * Usage:
 *   php generate-blog-drafts.php          (Generates / updates drafts deterministically)
 *   php generate-blog-drafts.php --force  (Forces refresh of automated draft fields)
 */

if (php_sapi_name() === 'cli') {
    $force = in_array('--force', $argv ?? []);
    $results = generate_blog_drafts($force);
    echo "=== BLOG DRAFT GENERATION COMPLETE ===\n";
    echo "Total Projects: " . $results['total_projects'] . "\n";
    echo "Total Article Definitions: " . $results['total_definitions'] . "\n";
    echo "New Drafts Created: " . $results['created'] . "\n";
    echo "Updated/Preserved: " . $results['updated'] . "\n";
    echo "Flagged for Review: " . $results['review_flagged'] . "\n";
    foreach ($results['log'] as $msg) {
        echo " - " . $msg . "\n";
    }
}

function get_projects_registry() {
    return [
        'vaze_realty' => [
            'project_id' => 'vaze_realty',
            'title' => 'Vaze Realty Platform',
            'client' => 'Vaze Realty',
            'experience_id' => 'exp_vaze',
            'role' => 'Senior Web Developer',
            'contribution' => 'Custom full-stack WordPress architecture, RESO Web API property sync, interactive polygon & radius Google Maps search UI, role-based dashboards (Agent, Client, Manager), and GoHighLevel / Follow Up Boss lead funnels.',
            'category' => 'Real Estate UI & MLS Integration',
            'tags' => ['WordPress', 'RESO API', 'Google Maps API', 'Follow Up Boss', 'GoHighLevel'],
            'cover_image' => 'assets/images/projects/Search-Map.png',
            'cover_alt' => 'Vaze Realty Interactive Map Search and Dashboard Interface',
            'live_url' => 'https://vazerealty.com/',
            'summary' => 'A custom real-estate platform with RESO Web API property search, interactive map radius and polygon search tools, role-based dashboards, and lead capture funnels.',
            'challenge' => 'Presenting interactive map property search, radius tools, status indicators, RESO Web API data sync, and client listing views in a clean, high-performance UI.',
            'solution' => 'Designed custom floating map filter controls, responsive property card grids, protected dashboard views for agents and clients, and automated CRM lead flows.',
            'has_verified_docs' => true,
            'source_ref' => 'data/custom-projects.json (web_001), index.html (#portfolio, #experience, modal-vaze)'
        ],
        'salescreator' => [
            'project_id' => 'salescreator',
            'title' => 'SalesCreator PropTech & Automation Platform',
            'client' => 'SalesCreators',
            'experience_id' => 'exp_salescreators',
            'role' => 'WordPress Developer',
            'contribution' => 'Developed custom property data widgets (Missed Opportunities, Withdrawn, Expiring Soon), Stripe checkout and mobile pricing sliders, automated Stripe webhook handlers for subscriptions/cancellations, Slack notifications, and Eagle CRM listing feeds.',
            'category' => 'PropTech & Automation',
            'tags' => ['WordPress', 'REST API', 'Stripe Webhooks', 'Slack API', 'Eagle CRM'],
            'cover_image' => 'assets/images/logos/navbar-brand.png',
            'cover_alt' => 'SalesCreator PropTech Platform Branding and Automation Interface',
            'live_url' => '',
            'summary' => 'Custom WordPress tools for real estate property feeds, automated Stripe subscription webhooks, Slack channel alerts, and Eagle CRM integration.',
            'challenge' => 'Processing high-frequency Stripe subscription webhooks reliably while synchronizing property data widgets and team Slack alerts without race conditions or page latency.',
            'solution' => 'Built modular WordPress webhook endpoints with verification, decoupled asynchronous Slack notification dispatches, and responsive property slider widgets.',
            'has_verified_docs' => true,
            'source_ref' => 'index.html (#portfolio, #experience, modal-salescreator)'
        ],
        'landways_cargo' => [
            'project_id' => 'landways_cargo',
            'title' => 'Landways Cargo Truck Manifest System',
            'client' => 'Landways Cargo Service',
            'experience_id' => null, // Independent client project
            'role' => 'Lead Software / Plugin Developer',
            'contribution' => 'Engineered custom WordPress plugin "Truck Manifest" (v14.7) with custom MySQL tables, Excel spreadsheet parser (PhpSpreadsheet/Composer), role-based billing dashboards, live waybill search, and TCPDF report generation.',
            'category' => 'Logistics & Custom Plugin Engineering',
            'tags' => ['WordPress', 'PHP', 'MySQL', 'PhpSpreadsheet', 'TCPDF', 'Custom Plugin'],
            'cover_image' => 'assets/images/projects/Active plugin.png',
            'cover_alt' => 'Landways Cargo Truck Manifest Plugin Interface and Waybill Dashboard',
            'live_url' => '',
            'summary' => 'A custom logistics plugin engineered for Landways Cargo to parse Excel manifests into structured MySQL records with real-time status tracking, role assignment, and PDF billing reports.',
            'challenge' => 'Automating manual cargo waybills data entry from varied Excel manifests while calculating collect/prepaid freight balances, aging statuses, and generating PDF invoices.',
            'solution' => 'Engineered custom database schema, robust PHP chunked Excel extraction pipeline, dynamic AJAX filter and status polling scripts, and automated TCPDF invoice generation.',
            'has_verified_docs' => true,
            'source_ref' => 'plugins/truck-manifest/ (truck-manifest.php, class-truck-manifest.php), data/custom-projects.json (web_002)'
        ],
        'iconelect' => [
            'project_id' => 'iconelect',
            'title' => 'ICON Elect Reviewer Portal & Visual Identity',
            'client' => 'ICON Elect',
            'experience_id' => null, // Independent client project
            'role' => 'Lead WordPress & Integration Developer / Brand Designer',
            'contribution' => 'Engineered secure reviewer portal using time-expiring token access, custom PHP write-back endpoints to Airtable REST API, Make automation email scenarios, and designed 3D emblem branding assets.',
            'category' => 'Secure Portals & 3D Branding',
            'tags' => ['WordPress', 'Airtable API', 'Make Automation', '3D Emblem', 'Secure Tokens'],
            'cover_image' => 'assets/images/logos/Products/iconelect/3D-ICONELECT-icon-v2.png',
            'cover_alt' => 'ICON Elect 3D Emblem and Reviewer Portal Interface',
            'live_url' => 'https://iconelect.org/',
            'summary' => 'A secure citizenship application and reviewer portal connected to Airtable with automated operational workflows and 3D visual identity design.',
            'challenge' => 'Designing secure citizenship and review portal workflows without exposing underlying Airtable database keys, while crafting a modern 3D emblem identity.',
            'solution' => 'Engineered token-secured reviewer access, custom PHP write-back endpoints to Airtable, automated Make notification scenarios, and 3D brand assets.',
            'has_verified_docs' => true,
            'source_ref' => 'data/custom-projects.json (logo_001), index.html (#portfolio, modal-iconelect)'
        ],
        'raviv_casuals' => [
            'project_id' => 'raviv_casuals',
            'title' => 'Raviv Casuals Footwear Brand & Packaging',
            'client' => 'Raviv Casuals',
            'experience_id' => null, // Independent client project
            'role' => 'WordPress/WooCommerce Developer & Packaging Designer',
            'contribution' => 'Custom WooCommerce shop styling and plugin extensions, combined with physical product packaging graphics (shoe box layouts, tote bags, dust bags, slides graphics).',
            'category' => 'E-commerce & Brand UX',
            'tags' => ['WooCommerce', 'WordPress', 'Luxury Packaging', 'Custom Plugin'],
            'cover_image' => 'assets/images/logos/Products/ravivcasuals/Box design raviv logo.png',
            'cover_alt' => 'Raviv Casuals Luxury Packaging and Footwear Brand Interface',
            'live_url' => '',
            'summary' => 'A responsive premium-footwear e-commerce storefront combining custom WooCommerce functionality with luxury shoe box graphics, tote bags, and dust bag packaging graphics.',
            'challenge' => 'Translating luxury footwear branding into custom physical packaging (box design, dust bags, tote prints) and a seamless e-commerce purchasing journey.',
            'solution' => 'Developed custom WooCommerce layout enhancements, minimal brand storytelling components, and production-ready packaging print artwork.',
            'has_verified_docs' => true,
            'source_ref' => 'data/custom-projects.json (logo_002), index.html (#portfolio)'
        ],
        'harrells_biscuits' => [
            'project_id' => 'harrells_biscuits',
            'title' => "Harrell's ButterCream Biscuits Packaging & Signage",
            'client' => "Harrell's ButterCream Biscuits",
            'experience_id' => null, // Independent client project
            'role' => 'Packaging & Brand Designer',
            'contribution' => 'Crafted retail packaging label layouts (front & side view mockups), typography guidelines, and promotional A-frame signage identity.',
            'category' => 'Food Packaging & Retail Identity',
            'tags' => ['Label Design', 'Product Packaging', 'Signage', 'Retail Branding'],
            'cover_image' => "assets/images/logos/Products/harrell-bcb/front view label.png",
            'cover_alt' => "Harrell's ButterCream Biscuits Packaging Label and Signage Design",
            'live_url' => '',
            'summary' => "Retail product packaging labels, front and side view label mockups, and promotional A-frame signage identity crafted for 'A Good Biscuit'.",
            'challenge' => 'Creating appetizing, compliant product label designs for retail containers alongside eye-catching outdoor store signage.',
            'solution' => 'Crafted front and side view container packaging graphics, typography layouts, and promotional A-frame identity assets for retail promotion.',
            'has_verified_docs' => true,
            'source_ref' => 'data/custom-projects.json (logo_003), index.html (#portfolio)'
        ],
        'll_harrell' => [
            'project_id' => 'll_harrell',
            'title' => 'L.L. Harrell Realty & Brokerage Modernization',
            'client' => 'L.L. Harrell Realty',
            'experience_id' => null, // Independent client project
            'role' => 'Web Developer & Visual Identity Designer',
            'contribution' => 'Modernized corporate real estate visual mark/vector iconography and developed custom PHP/MySQL property search functionality.',
            'category' => 'Realty Logo & Listing Search',
            'tags' => ['WordPress', 'PHP', 'MySQL', 'Realty Logo', 'Vector Mark'],
            'cover_image' => 'assets/images/logos/Products/llharrell/HarrellLogo.png',
            'cover_alt' => 'L.L. Harrell Realty Logo Modernization and Property Search Interface',
            'live_url' => '',
            'summary' => 'A professional commercial real-estate presence supported by modernized vector logo branding, custom property-search functionality, and structured listing management.',
            'challenge' => 'Modernizing corporate real-estate visual identity while upgrading legacy property search functionality to modern PHP and MySQL standards.',
            'solution' => 'Designed sleek vector mark iconography, responsive search interfaces, and structured property data mapping.',
            'has_verified_docs' => true,
            'source_ref' => 'data/custom-projects.json (logo_004), index.html (#portfolio)'
        ],
        'taxhaus' => [
            'project_id' => 'taxhaus',
            'title' => 'Taxhaus Business Logics Corporate Visual Identity',
            'client' => 'TAXHAUS BUSINESS GROUP LLC',
            'experience_id' => 'exp_taxhaus',
            'role' => 'Senior Web Developer & Brand Specialist',
            'contribution' => 'Led web development, custom theme customization, database integrations, automated workflows, and engineered corporate vector emblem system.',
            'category' => 'Corporate Identity & Visual Systems',
            'tags' => ['Brand Identity', 'Corporate Emblem', 'Visual System', 'Vector Mark'],
            'cover_image' => 'assets/images/logos/Products/tbl/Taxhaus.png',
            'cover_alt' => 'Taxhaus Business Logics Corporate Emblem and Brand Guidelines',
            'live_url' => '',
            'summary' => 'A comprehensive corporate logo mark, vector emblems, and visual identity guidelines designed for financial software consulting and tax logic services.',
            'challenge' => 'Establishing a trusted, corporate visual identity for tax logic and financial software consulting across digital, web, and document touchpoints.',
            'solution' => 'Engineered geometric corporate emblems, vector logo assets, color systems, and visual usage guidelines built for authority and clarity.',
            'has_verified_docs' => true,
            'source_ref' => 'data/custom-projects.json (logo_005), index.html (#experience, #portfolio)'
        ],
        'cruise_brisbane' => [
            'project_id' => 'cruise_brisbane',
            'title' => 'Cruise Brisbane River Tourism & Booking Platform',
            'client' => 'Cruise Brisbane River',
            'experience_id' => 'exp_salescreators',
            'role' => 'WordPress Developer (Client project via SalesCreators)',
            'contribution' => 'Developed custom Gutenberg blocks with editable ACF fields for pricing/itineraries, filterable category UI (Sunset Sails, Culture, Charters), and Rezdy tour booking integration.',
            'category' => 'Tourism & Booking Systems',
            'tags' => ['WordPress', 'Custom Blocks', 'Rezdy API', 'ACF Pro'],
            'cover_image' => 'assets/images/logos/cruise brisbane river.png',
            'cover_alt' => 'Cruise Brisbane River Experience Layout and Rezdy Booking Widget',
            'live_url' => 'https://mattheww469.sg-host.com/',
            'summary' => 'A tourism experience website with editable cruise content, interactive filters, Rezdy booking API integration, and responsive custom block layouts.',
            'challenge' => 'Making cruise discovery and tour reservation seamless while empowering site managers to update itineraries, pricing, and specs via custom Gutenberg blocks.',
            'solution' => 'Built ACF-powered custom block controls, interactive itinerary filter categories, and embedded Rezdy booking widget integration.',
            'has_verified_docs' => true,
            'source_ref' => 'index.html (#portfolio, modal-cruisebrisbane)'
        ],
        'clark_partners' => [
            'project_id' => 'clark_partners',
            'title' => 'Clark Partners Gutenberg Theme & Mortgage Tooling',
            'client' => 'Clark Partners',
            'experience_id' => 'exp_salescreators',
            'role' => 'WordPress Developer (Client project via SalesCreators)',
            'contribution' => 'Registered custom ACF field groups for Buy/Rent/Sold/Agents pages, built responsive mortgage calculators, and diagnosed PHP template load times on SiteGround SFTP.',
            'category' => 'Gutenberg & ACF Development',
            'tags' => ['WordPress', 'PHP', 'ACF Blocks', 'Gutenberg', 'Calculators'],
            'cover_image' => 'assets/images/logos/Clark Partners RGB.png',
            'cover_alt' => 'Clark Partners Real Estate Theme and Finance Calculator Interface',
            'live_url' => '',
            'summary' => 'A flexible real-estate website system featuring reusable ACF-powered Gutenberg blocks, interactive mortgage calculators, and appraisal pathways.',
            'challenge' => 'Developing flexible content controls for Buy, Rent, Sold, and Agent pages while building interactive finance calculators and optimizing PHP page performance.',
            'solution' => 'Registered custom ACF field groups, built responsive mortgage calculation tools in JS/PHP, and audited template queries for fast page loads.',
            'has_verified_docs' => true,
            'source_ref' => 'index.html (#portfolio, modal-clarkpartners)'
        ],
        'fresh_collective' => [
            'project_id' => 'fresh_collective',
            'title' => 'The Fresh Collective Custom Catering Theme',
            'client' => 'The Fresh Collective',
            'experience_id' => 'exp_salescreators',
            'role' => 'WordPress Developer (Client project via SalesCreators)',
            'contribution' => 'Custom theme engineering supporting a high-end Australian catering brand with reusable menu and venue components.',
            'category' => 'Custom Theme Engineering',
            'tags' => ['WordPress', 'PHP', 'Custom Theme', 'Content UX'],
            'cover_image' => 'assets/images/logos/the fresh collective.png',
            'cover_alt' => 'The Fresh Collective Catering Platform and Menu Experience',
            'live_url' => 'https://thefreshcollective.com.au/',
            'summary' => 'A premium Australian catering and events platform engineered with custom WordPress components and curated hospitality content experiences.',
            'challenge' => 'Presenting high-end catering menus, event venues, and seasonal dining packages with effortless navigation and fast mobile rendering.',
            'solution' => 'Engineered a lightweight custom PHP theme, modular venue grids, interactive menu showcases, and streamlined enquiry CTAs.',
            'has_verified_docs' => true,
            'source_ref' => 'index.html (#portfolio)'
        ],
        'riverlife' => [
            'project_id' => 'riverlife',
            'title' => 'Riverlife Outdoor Experiences & Booking Integration',
            'client' => 'Riverlife',
            'experience_id' => 'exp_salescreators',
            'role' => 'WordPress Developer (Client project via SalesCreators)',
            'contribution' => 'Configured form integrations, Mailchimp subscriber field syncing, and Rezdy booking widget responsiveness in Divi theme.',
            'category' => 'Marketing & Integration',
            'tags' => ['WordPress', 'Divi', 'Mailchimp', 'Rezdy'],
            'cover_image' => 'assets/images/logos/River-Life-Logo-Landscape-White.webp',
            'cover_alt' => 'Riverlife Outdoor Activities Booking and Mailchimp Form Integration',
            'live_url' => 'https://riverlife.com.au/',
            'summary' => 'Ongoing development and support for a high-traffic experience brand, connecting outdoor tour bookings, Mailchimp campaign forms, and page performance.',
            'challenge' => 'Integrating third-party Rezdy tour widgets and Mailchimp subscription forms into existing Divi theme layouts without layout breakage or slow load times.',
            'solution' => 'Configured field mappings, responsive widget styling, campaign lead triggers, and custom CSS layout safeguards.',
            'has_verified_docs' => true,
            'source_ref' => 'index.html (#portfolio, modal-riverlife)'
        ]
    ];
}

function get_articles_definitions() {
    return [
        // 1. Vaze Realty - Project Story
        [
            'article_key' => 'vaze_realty_story',
            'project_id' => 'vaze_realty',
            'type' => 'project_story',
            'slug' => 'vaze-realty-platform-architecture',
            'title' => 'Vaze Realty: Building an MLS-Integrated Real Estate Platform',
            'topic' => 'Full platform architecture, RESO data mapping, and custom client/agent portals',
            'excerpt' => 'A deep dive into engineering the Vaze Realty platform: connecting RESO Web API feeds, custom Google Maps drawing filters, and role-based client portals on WordPress.'
        ],
        // 2. Vaze Realty - Development Fix
        [
            'article_key' => 'vaze_realty_fix',
            'project_id' => 'vaze_realty',
            'type' => 'development_fix',
            'slug' => 'vaze-realty-interactive-map-performance',
            'title' => 'Solving Map Marker Clustering & Radius Filtering in Real Estate Search',
            'topic' => 'Google Maps API viewport bounds and query optimization',
            'excerpt' => 'How we resolved polygon coordinate rendering bottlenecks and optimized dynamic listing card synchronizations during radius and map dragging.'
        ],
        // 3. SalesCreator - Project Story
        [
            'article_key' => 'salescreator_story',
            'project_id' => 'salescreator',
            'type' => 'project_story',
            'slug' => 'salescreator-stripe-webhooks-automation',
            'title' => 'SalesCreator: Automating Stripe Subscriptions & Real-Time Slack Workflows',
            'topic' => 'PropTech subscription handling, webhook idempotency, and Eagle CRM sync',
            'excerpt' => 'Implementing mission-critical Stripe webhook handlers, mobile plan pricing calculators, and immediate Slack team notifications for SalesCreator.'
        ],
        // 4. Landways Cargo - Project Story
        [
            'article_key' => 'landways_cargo_story',
            'project_id' => 'landways_cargo',
            'type' => 'project_story',
            'slug' => 'landways-cargo-truck-manifest-system',
            'title' => 'Landways Cargo: Developing the Truck Manifest WordPress Plugin',
            'topic' => 'Custom plugin engineering, Excel data extraction, and logistics tracking',
            'excerpt' => 'How I designed and engineered the Truck Manifest plugin (v14.7) to extract Excel manifest shipments, compute freight aging, and generate automated PDF reports.'
        ],
        // 5. Landways Cargo - Development Fix
        [
            'article_key' => 'landways_cargo_fix',
            'project_id' => 'landways_cargo',
            'type' => 'development_fix',
            'slug' => 'landways-cargo-excel-parsing-chunking',
            'title' => 'Handling Large Excel Manifest Uploads & MySQL Chunking in WordPress',
            'topic' => 'PhpSpreadsheet memory limits, transaction rollbacks, and schema indexing',
            'excerpt' => 'Overcoming PHP memory exhaustion during massive truck manifest spreadsheet uploads through chunked streaming and custom indexed table structures.'
        ],
        // 6. ICON Elect - Project Story
        [
            'article_key' => 'iconelect_story',
            'project_id' => 'iconelect',
            'type' => 'project_story',
            'slug' => 'icon-elect-secure-reviewer-portal',
            'title' => 'ICON Elect: Building a Token-Secured Reviewer Portal on Airtable',
            'topic' => 'Security without exposing database keys, Make automation scenarios',
            'excerpt' => 'Architecture of the ICON Elect reviewer portal: issuing time-expiring review tokens, validating approvals, and writing decision data back to Airtable via custom PHP.'
        ],
        // 7. Raviv Casuals - Design Notes
        [
            'article_key' => 'raviv_casuals_design',
            'project_id' => 'raviv_casuals',
            'type' => 'design_notes',
            'slug' => 'raviv-casuals-luxury-packaging-design',
            'title' => 'Raviv Casuals: Luxury Footwear Packaging & E-commerce Brand UX',
            'topic' => 'Shoe box graphics, dust bag prints, and minimal WooCommerce storefront',
            'excerpt' => 'Designing physical luxury packaging assets—from tote bags to custom shoe boxes—while maintaining brand consistency across the digital WooCommerce experience.'
        ],
        // 8. Harrell\'s Biscuits - Design Notes
        [
            'article_key' => 'harrells_biscuits_design',
            'project_id' => 'harrells_biscuits',
            'type' => 'design_notes',
            'slug' => 'harrells-buttercream-biscuits-label-packaging',
            'title' => "Harrell's ButterCream Biscuits: Retail Packaging Labels & Signage",
            'topic' => 'Food container packaging compliance, A-frame signage identity',
            'excerpt' => "Crafting visual identity and print packaging labels for Harrell's ButterCream Biscuits, balancing appetizing retro aesthetics with retail display legibility."
        ],
        // 9. L.L. Harrell - Project Story
        [
            'article_key' => 'll_harrell_story',
            'project_id' => 'll_harrell',
            'type' => 'project_story',
            'slug' => 'll-harrell-realty-brand-modernization-search',
            'title' => 'L.L. Harrell Realty: Brand Modernization & Custom Property Search',
            'topic' => 'Vector logo mark modernization and custom PHP listing queries',
            'excerpt' => 'Modernizing corporate realty branding into a clean vector identity while engineering dependable PHP/MySQL property search tools.'
        ],
        // 10. Taxhaus - Design Notes
        [
            'article_key' => 'taxhaus_design',
            'project_id' => 'taxhaus',
            'type' => 'design_notes',
            'slug' => 'taxhaus-business-logics-corporate-emblem-system',
            'title' => 'Taxhaus Business Logics: Engineering a Corporate Emblem System',
            'topic' => 'Corporate emblem geometry, vector guidelines, financial consulting identity',
            'excerpt' => 'Designing an authoritative, geometric corporate brand system for tax logic and financial software consulting across digital, web, and corporate assets.'
        ],
        // 11. Cruise Brisbane - Project Story
        [
            'article_key' => 'cruise_brisbane_story',
            'project_id' => 'cruise_brisbane',
            'type' => 'project_story',
            'slug' => 'cruise-brisbane-river-rezdy-booking-blocks',
            'title' => 'Cruise Brisbane River: Custom Gutenberg Blocks & Rezdy Tour Booking',
            'topic' => 'Modular ACF blocks, interactive cruise filters, Rezdy API integration',
            'excerpt' => 'Empowering marketing editors with reusable ACF blocks for pricing and itineraries while integrating responsive Rezdy tour booking widgets.'
        ],
        // 12. Clark Partners - Development Fix
        [
            'article_key' => 'clark_partners_fix',
            'project_id' => 'clark_partners',
            'type' => 'development_fix',
            'slug' => 'clark-partners-mortgage-calculator-performance',
            'title' => 'Clark Partners: Building Responsive Mortgage Calculators & ACF Optimization',
            'topic' => 'Mortgage calculation accuracy, ACF template query audits',
            'excerpt' => 'Developing interactive real estate mortgage and appraisal calculation components without dragging down WordPress page rendering performance.'
        ],
        // 13. The Fresh Collective - Project Story
        [
            'article_key' => 'fresh_collective_story',
            'project_id' => 'fresh_collective',
            'type' => 'project_story',
            'slug' => 'fresh-collective-hospitality-theme-engineering',
            'title' => 'The Fresh Collective: Lightweight Custom Theme for Premium Catering',
            'topic' => 'Hospitality content UX, venue showcases, fast mobile rendering',
            'excerpt' => 'Engineering a bespoke PHP WordPress theme to showcase seasonal dining menus and event venues for a prominent Australian catering brand.'
        ],
        // 14. Riverlife - Development Fix
        [
            'article_key' => 'riverlife_fix',
            'project_id' => 'riverlife',
            'type' => 'development_fix',
            'slug' => 'riverlife-divi-rezdy-mailchimp-integration',
            'title' => 'Riverlife: Stabilizing Rezdy Widgets & Mailchimp Sync in Divi',
            'topic' => 'Third-party script collisions, responsive layout fixes, form field mapping',
            'excerpt' => 'Resolving styling conflicts and subscriber synchronization issues between third-party Rezdy booking modals and Mailchimp forms inside complex Divi layouts.'
        ]
    ];
}

function generate_blog_drafts($force = false) {
    $base_dir = __DIR__;
    $articles_file = $base_dir . '/data/articles.json';
    $experiences_file = $base_dir . '/data/experiences.json';

    $projects_registry = get_projects_registry();
    $articles_definitions = get_articles_definitions();

    $experiences = [];
    if (file_exists($experiences_file)) {
        $experiences = json_decode(file_get_contents($experiences_file), true) ?: [];
    }
    $exp_map = [];
    foreach ($experiences as $e) {
        $exp_map[$e['id']] = $e;
    }

    $existing_articles = [];
    if (file_exists($articles_file)) {
        $existing_articles = json_decode(file_get_contents($articles_file), true) ?: [];
    }

    // Map existing articles by slug and article_key
    $existing_by_key = [];
    $existing_by_slug = [];
    foreach ($existing_articles as $art) {
        if (!empty($art['article_key'])) {
            $existing_by_key[$art['article_key']] = $art;
        }
        if (!empty($art['slug'])) {
            $existing_by_slug[$art['slug']] = $art;
        }
    }

    $created_count = 0;
    $updated_count = 0;
    $flagged_count = 0;
    $logs = [];
    $final_articles = [];

    $now = date('Y-m-d H:i:s');
    $pub_date = date('Y-m-d H:i:s', strtotime('-3 days'));

    foreach ($articles_definitions as $def) {
        $key = $def['article_key'];
        $pid = $def['project_id'];
        $proj = $projects_registry[$pid] ?? null;

        if (!$proj) {
            $logs[] = "Skipped article definition {$key}: Project {$pid} not found in registry.";
            continue;
        }

        $exp = !empty($proj['experience_id']) ? ($exp_map[$proj['experience_id']] ?? null) : null;

        // Calculate source fingerprint
        $fingerprint = md5(json_encode([
            $def['title'],
            $def['topic'],
            $proj['summary'],
            $proj['challenge'],
            $proj['solution'],
            $proj['role'],
            $proj['contribution'],
            $exp ? $exp['organization'] : 'Independent'
        ]));

        // Check if existing article matches
        $existing = $existing_by_key[$key] ?? ($existing_by_slug[$def['slug']] ?? null);

        if ($existing) {
            $source_changed = isset($existing['source_fingerprint']) && $existing['source_fingerprint'] !== $fingerprint;
            if ($source_changed) {
                $existing['review_flag'] = true;
                $existing['review_note'] = 'Source project/experience information changed since last generation.';
                $flagged_count++;
                $logs[] = "Flagged for review (source changed): {$def['title']}";
            }

            // Update relationship IDs cleanly
            $existing['project_id'] = $pid;
            $existing['project_ids'] = [$pid];
            $existing['experience_id'] = $proj['experience_id'];
            $existing['article_key'] = $key;
            $existing['article_type'] = $def['type'];
            $existing['client_name'] = $proj['client'];

            if ($force) {
                $body = build_structured_article_content($def, $proj, $exp);
                $existing['title'] = $def['title'];
                $existing['slug'] = $def['slug'];
                $existing['category'] = $proj['category'];
                $existing['tags'] = $proj['tags'];
                $existing['cover_image'] = $proj['cover_image'];
                $existing['cover_alt'] = $proj['cover_alt'];
                $existing['excerpt'] = $def['excerpt'];
                $existing['body'] = $body;
                $existing['reading_time'] = calculate_reading_time($body);
                $existing['source_fingerprint'] = $fingerprint;
                $existing['updated_at'] = $now;
                $logs[] = "Force refreshed draft: {$def['title']}";
            } else {
                $logs[] = "Preserved existing article: {$existing['title']} [Status: " . ($existing['status'] ?? 'draft') . "]";
            }

            $final_articles[] = $existing;
            $updated_count++;
        } else {
            // Create new structured draft
            $body = build_structured_article_content($def, $proj, $exp);
            $reading_time = calculate_reading_time($body);

            $new_article = [
                'id' => 'art_' . substr(md5($key), 0, 8),
                'article_key' => $key,
                'project_id' => $pid,
                'project_ids' => [$pid],
                'experience_id' => $proj['experience_id'],
                'article_type' => $def['type'],
                'slug' => $def['slug'],
                'title' => $def['title'],
                'client_name' => $proj['client'],
                'category' => $proj['category'],
                'tags' => $proj['tags'],
                'cover_image' => $proj['cover_image'],
                'cover_alt' => $proj['cover_alt'],
                'excerpt' => $def['excerpt'],
                'body' => $body,
                'author' => 'Ian Ceazar A. Escalante',
                'status' => 'draft', // Saved as draft for review before publication
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
            $logs[] = "Created new draft ({$def['type']}): {$def['title']} (Slug: {$def['slug']})";
        }
    }

    file_put_contents($articles_file, json_encode($final_articles, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    return [
        'total_projects' => count($projects_registry),
        'total_definitions' => count($articles_definitions),
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

function build_structured_article_content($def, $proj, $exp) {
    $title = htmlspecialchars($def['title']);
    $type = $def['type'];
    $client = htmlspecialchars($proj['client']);
    $role = htmlspecialchars($proj['role']);
    $contribution = htmlspecialchars($proj['contribution']);
    $summary = htmlspecialchars($proj['summary']);
    $challenge = htmlspecialchars($proj['challenge']);
    $solution = htmlspecialchars($proj['solution']);
    $cover = htmlspecialchars($proj['cover_image']);
    $alt = htmlspecialchars($proj['cover_alt']);
    $tags_str = htmlspecialchars(implode(', ', $proj['tags']));

    $org_context = '';
    if ($exp) {
        $org_name = htmlspecialchars($exp['organization']);
        $period = htmlspecialchars($exp['period']);
        $eng_type = htmlspecialchars($exp['engagement_type']);
        $org_context = "<p class=\"article-context-banner\"><strong>Professional Context:</strong> This work was executed during my role as <em>{$role}</em> at <strong>{$org_name}</strong> ({$period} &bull; {$eng_type}).</p>";
    } else {
        $org_context = "<p class=\"article-context-banner\"><strong>Professional Context:</strong> This was delivered as an independent specialized technical project for <strong>{$client}</strong>.</p>";
    }

    if ($type === 'development_fix') {
        return <<<HTML
<p class="lead">In high-traffic WordPress applications and custom integrations, unexpected edge cases, database latency, and third-party API collisions can introduce friction. Here is how I diagnosed, repaired, and verified the technical solution for <strong>{$client}</strong>.</p>

{$org_context}

<h2>1. The Problem &amp; Observed Symptoms</h2>
<p>During live operations, we encountered specific performance or workflow obstacles:</p>
<blockquote>{$challenge}</blockquote>
<p>Rather than applying surface-level patches, my objective was identifying the root architectural cause and implementing a permanent, resilient fix.</p>

<figure class="article-figure">
  <img src="{$cover}" alt="{$alt}" class="article-img">
  <figcaption>{$alt} — Technical implementation area reviewed and optimized by Ian Ceazar A. Escalante.</figcaption>
</figure>

<h2>2. My Diagnostic Process &amp; Technical Role</h2>
<p>Serving as <strong>{$role}</strong>, I took direct technical ownership of diagnosing and resolving this issue. My scope included:</p>
<ul>
  <li>Auditing server logs, HTTP response payloads, and database queries.</li>
  <li>Isolating execution bottlenecks across <strong>{$tags_str}</strong>.</li>
  <li>Developing local reproduction tests before deploying fixes to production.</li>
</ul>

<h2>3. What I Fixed &amp; Implemented</h2>
<p>{$contribution}</p>
<p>{$solution}</p>

<h2>4. Key Engineering Decisions</h2>
<ul>
  <li><strong>Safe Fallbacks:</strong> Ensured graceful error handling so external API downtime never breaks the visitor-facing layout.</li>
  <li><strong>Database &amp; Memory Hygiene:</strong> Eliminated redundant transient queries and optimized database indexing.</li>
  <li><strong>Code Maintainability:</strong> Documented hooks and filter callbacks to make future modifications straightforward.</li>
</ul>

<h2>5. Verified Outcome</h2>
<p>The fix was thoroughly tested across staging environments and successfully pushed to production. The workflow now operates smoothly without error spikes or layout inconsistencies.</p>
HTML;
    }

    if ($type === 'design_notes') {
        return <<<HTML
<p class="lead">Effective visual communication requires balancing brand character with functional readability. In this project for <strong>{$client}</strong>, I crafted design assets tailored to their exact operational and retail requirements.</p>

{$org_context}

<h2>1. Creative Brief &amp; Design Objectives</h2>
<p>{$summary}</p>
<blockquote>{$challenge}</blockquote>

<figure class="article-figure">
  <img src="{$cover}" alt="{$alt}" class="article-img">
  <figcaption>{$alt} — Design assets crafted by Ian Ceazar A. Escalante.</figcaption>
</figure>

<h2>2. My Role &amp; Creative Scope</h2>
<p>As <strong>{$role}</strong>, I was responsible for delivering production-ready visual assets. My scope encompassed:</p>
<ul>
  <li>{$contribution}</li>
  <li>Establishing unified typography, color harmony, and vector iconography.</li>
  <li>Delivering print-ready high-resolution files and optimized digital formats.</li>
</ul>

<h2>3. Design Decisions &amp; Visual Strategy</h2>
<p>{$solution}</p>
<p>Every element was designed intentionally—ensuring clean scaling whether displayed on a mobile screen, luxury product box, or retail signage.</p>

<h2>4. Outcome &amp; Production Verification</h2>
<p>The approved assets were packaged, verified for color fidelity and dimension accuracy, and deployed across active customer touchpoints.</p>
HTML;
    }

    // Default: project_story
    return <<<HTML
<p class="lead">Building custom WordPress platforms means delivering systems that solve real operational needs. On <strong>{$client}</strong>, my work focused on creating dependable architecture, intuitive interfaces, and streamlined automation.</p>

{$org_context}

<h2>1. Project Overview &amp; Context</h2>
<p>{$summary}</p>

<figure class="article-figure">
  <img src="{$cover}" alt="{$alt}" class="article-img">
  <figcaption>{$alt} — Layout and systems developed by Ian Ceazar A. Escalante.</figcaption>
</figure>

<h2>2. The Challenge &amp; Requirements</h2>
<blockquote>{$challenge}</blockquote>
<p>The goal was to build a reliable solution that internal teams can easily manage while providing end users with a frictionless experience.</p>

<h2>3. My Role &amp; Technical Scope</h2>
<p>In my position as <strong>{$role}</strong>, I was directly responsible for:</p>
<ul>
  <li>{$contribution}</li>
  <li>Engineering secure data pipelines utilizing <strong>{$tags_str}</strong>.</li>
  <li>Ensuring WCAG accessibility, responsive viewport scaling, and performant page loads.</li>
</ul>

<h2>4. What I Built &amp; Implemented</h2>
<p>{$solution}</p>

<h2>5. Engineering Best Practices Applied</h2>
<ul>
  <li><strong>Security First:</strong> Strict input sanitization, output escaping, and nonce token verification on all user actions.</li>
  <li><strong>Decoupled Architecture:</strong> Clean separation between business logic and display presentation for long-term maintainability.</li>
  <li><strong>Performance:</strong> Lightweight asset loading and efficient API response caching.</li>
</ul>

<h2>6. Verified Results</h2>
<p>The implementation was verified across desktop and mobile browsers and deployed live. It stands as an example of WordPress engineered beyond simple brochure sites into a true business platform.</p>
HTML;
}
