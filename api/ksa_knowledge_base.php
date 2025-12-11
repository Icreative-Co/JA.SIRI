<?php
/**
 * KSA Knowledge Base
 * 
 * This file contains comprehensive information about Kenya Scouts Association
 * to enhance the AI assistant's responses. The AI will use this context
 * when answering user questions.
 */

$ksa_knowledge = [
    'organization' => [
        'name' => 'Kenya Scouts Association',
        'motto' => 'Be Prepared • Kuwa Tayari',
        'headquarters' => 'Rowallan Camp, Nairobi',
        'contact' => 'info@kenyascouts.org',
        'phone' => '020 2020819',
        'founded' => 1910,
        'counties' => 47,
        'youth_served' => '500,000+',
        'mission' => 'Developing responsible citizens through scouting programs'
    ],
    
    'shop_info' => [
        'name' => 'Kenya Scouts Shop',
        'tagline' => 'Lipa Mdogo Mdogo - Official Scout Uniforms & Merchandise',
        'paybill' => [
            'bp_shop' => '4041953',
            'nakuru' => '4041969',
            'mombasa' => '4041961',
            'kakamega' => '4041965',
            'nyeri' => '4041959',
            'rowallan' => '4041955',
            'kisumu' => '4041967',
            'embu' => '4041957',
            'eldoret' => '4041963'
        ],
        'locations' => [
            'BP Shop' => 'Baden Powell House, Parliament Road, next to St. Johns Ambulance',
            'Nakuru' => 'Jennifer Riria Hub, Opposite Bontana Hotel, Tom Mboya Street',
            'Mombasa' => 'Jomo Kenyatta Avenue, Makini Herbs Building, next to Gurshan restaurant',
            'Kakamega' => 'Next to Barclays Bank, Kakamega-Kisumu highway',
            'Nyeri' => 'Nyeri Scouts Centre, Baden Powell Information Centre',
            'Rowallan' => 'Kibera Drive, opposite ASK show ground',
            'Kisumu' => 'Odinga Oginga road, Centre Court building, next to I&M bank',
            'Embu' => 'Embu Scouts Centre, next to Embu High Court',
            'Eldoret' => 'Former DCs premises, opposite Coca-Cola depot'
        ]
    ],
    
    'uniforms' => [
        'land_scouts_boys' => [
            'description' => 'Boy Scout Uniform (Land Scouts)',
            'items' => [
                'Shirt & Short' => '1200 - 1600 KES (by size)',
                'Beret' => '650 KES',
                'Belt' => '600 KES',
                'Pouch' => '300 KES',
                'Socks' => '250 KES',
                'Lanyard' => '150 KES',
                'Whistle' => '150 KES',
                'Scarf' => '150 KES',
                'Woggle' => '150 KES'
            ],
            'total_range' => '3600 - 4000 KES (complete uniform)',
            'sizes' => '18, 22, 24, 26, 28, 30, 32, 34, 36 (ages 5-13+ years)',
            'material' => 'Premium cotton/poly blend'
        ],
        'land_scouts_girls' => [
            'description' => 'Girl Scout Uniform (Land Scouts)',
            'items' => [
                'Dress' => '1200 - 1600 KES (by size)',
                'Beret' => '650 KES',
                'Belt' => '600 KES',
                'Pouch' => '300 KES',
                'Socks' => '250 KES',
                'Lanyard' => '150 KES',
                'Whistle' => '150 KES',
                'Scarf' => '150 KES',
                'Woggle' => '150 KES'
            ],
            'total_range' => '3600 - 4000 KES (complete uniform)',
            'sizes' => '26, 28, 30, 32, 34, 36, 38, 40, 42, 44 (ages 5-13+ years)',
            'material' => 'Premium cotton/poly blend'
        ],
        'air_sea_scouts_boys' => [
            'description' => 'Air/Sea Scout Boy Uniform',
            'total_range' => '1550 - 2360 KES (by style)',
            'colors' => 'Purple shirt/Navy blue shorts or trousers',
            'items' => [
                'Shirt S/S & Shorts' => '1550 - 1850 KES',
                'Shirt & Trouser' => '2260 KES',
                'Shirt L/S & Trouser' => '2360 KES'
            ],
            'sizes' => '18, 20, 22, 24, 26, 28, 30, 32, 34, 36, 38, 40, 42, 44, 46, 48'
        ],
        'secondary_school' => [
            'description' => 'Secondary School Scout Uniforms',
            'total_range' => '4200 - 5200 KES (complete set)',
            'items' => [
                'Trouser' => '1200-1100 KES',
                'Shirt L/S' => '1000 KES',
                'Shirt S/S' => '1000 KES',
                'Scout boots' => '3000 KES',
                'Overall' => '2000 KES'
            ]
        ]
    ],
    
    'accessories' => [
        'Scout Handbook' => '250 KES',
        'Patrol Leaders Manual' => '500 KES',
        'Scouts Handbook' => '250 KES',
        'Program Handbooks' => '200 KES',
        'Proficiency Badges' => '50 KES each',
        'World Badge' => '130 KES',
        'Scout Leaders Scarf' => '300 KES',
        'County Commissioners Scarf' => '500 KES',
        'Sub County Commissioner Scarf' => '500 KES',
        'Leaders Lanyard' => '150 KES',
        'Leaders Whistle' => '200 KES',
        'Hunting Knife & Sheath' => '500 KES',
        'Camp Knife & Sheath' => '350 KES',
        'Sleeping Bags' => '3000 KES',
        'Scout Flags' => '4000 KES',
        'East Africa Flag' => '3000 KES',
        'Kenya Flag' => '2500 KES',
        'BP Hat' => '8000 KES'
    ],
    
    'programs' => [
        'Beavers' => [
            'age' => '5-8 years',
            'handbook' => 'Sungura Handbook'
        ],
        'Cub Scouts' => [
            'age' => '8-11 years',
            'handbook' => 'Chipukizi Handbook'
        ],
        'Scouts' => [
            'age' => '11-14 years',
            'handbook' => 'Mwamba Handbook'
        ],
        'Venturers' => [
            'age' => '14-18 years'
        ],
        'Rovers' => [
            'age' => '18+ years',
            'handbook' => 'Rovering as Intended'
        ]
    ],
    
    'delivery_info' => [
        'free_pickup' => 'KSA Headquarters, Rowallan Camp, Nairobi',
        'nationwide_delivery' => 'Via G4S: 300-600 KES',
        'processing_time' => '24 hours after full payment',
        'payment_method' => 'M-Pesa Lipa Mdogo Mdogo (installments available)',
        'warranty' => '30-day quality guarantee'
    ],
    
    'values' => [
        'duty_to_god' => 'Spirituality and moral development',
        'duty_to_country' => 'Patriotism and civic responsibility',
        'duty_to_community' => 'Service and helping others',
        'duty_to_self' => 'Personal growth and self-discipline'
    ],
    
    'frequently_asked' => [
        'How do I order uniforms?' => 'Visit shop.php on this website, select items, and pay via M-Pesa STK Push. You can pay in installments.',
        'What sizes are available?' => 'We offer sizes for ages 5-13+ years. Check the product details for specific size charts.',
        'How long does delivery take?' => 'Orders are processed within 24 hours of full payment. Free pickup available at Rowallan Camp.',
        'Can I pay in installments?' => 'Yes! Our Lipa Mdogo Mdogo program allows you to pay any amount today with no interest or fees.',
        'What areas do you deliver to?' => 'Nationwide delivery available via G4S at 300-600 KES. Free pickup in Nairobi.',
        'Do you sell individual items?' => 'Yes, all items are available individually or as complete uniforms.',
        'What is your quality guarantee?' => 'We offer a 30-day quality guarantee on all products.',
        'How can I track my order?' => 'Visit check.php and enter your M-Pesa phone number to track payment status.'
    ]
];

// Return knowledge base as JSON for API access
function get_ksa_knowledge_json() {
    global $ksa_knowledge;
    return json_encode($ksa_knowledge, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}

// Get formatted context for AI model
function get_ksa_context() {
    global $ksa_knowledge;
    $context = "You are a Kenya Scouts Association (KSA) customer service AI assistant.\n\n";
    $context .= "ORGANIZATION INFO:\n";
    $context .= "- Name: " . $ksa_knowledge['organization']['name'] . "\n";
    $context .= "- Motto: " . $ksa_knowledge['organization']['motto'] . "\n";
    $context .= "- Contact: " . $ksa_knowledge['organization']['contact'] . "\n";
    $context .= "- Phone: " . $ksa_knowledge['organization']['phone'] . "\n";
    $context .= "- Headquarters: " . $ksa_knowledge['organization']['headquarters'] . "\n";
    $context .= "- Operating in 47 counties serving 500,000+ youth since 1910\n\n";
    
    $context .= "SHOP SERVICE:\n";
    $context .= "- Official name: Kenya Scouts Shop - Lipa Mdogo Mdogo\n";
    $context .= "- Offers: Scout uniforms, badges, handbooks, camping gear, and accessories\n";
    $context .= "- Payment: M-Pesa STK Push (Installments available - No interest, No fees)\n";
    $context .= "- Delivery: Free pickup at Rowallan Camp or nationwide via G4S (300-600 KES)\n";
    $context .= "- Processing: 24 hours after full payment\n";
    $context .= "- Guarantee: 30-day quality guarantee\n\n";
    
    $context .= "UNIFORM PRICING:\n";
    $context .= "- Boy Scout Complete Uniform: 3600-4000 KES\n";
    $context .= "- Girl Scout Complete Uniform: 3600-4000 KES\n";
    $context .= "- Air/Sea Scout Uniform: 1550-2360 KES (specific items)\n";
    $context .= "- Individual items available (Beret: 650 KES, Belt: 600 KES, etc.)\n\n";
    
    $context .= "CUSTOMER SERVICE:\n";
    $context .= "- Be helpful, friendly, and professional\n";
    $context .= "- Direct customers to shop.php for ordering\n";
    $context .= "- Direct customers to check.php for order tracking\n";
    $context .= "- Provide accurate pricing and delivery information\n";
    $context .= "- Answer questions about scouting programs and values\n";
    $context .= "- Use KSA branding: 'Be Prepared • Kuwa Tayari'\n";
    
    return $context;
}

// Function to get context for a specific query
function get_relevant_context($query) {
    global $ksa_knowledge;
    $query_lower = strtolower($query);
    $context = "";
    
    if (strpos($query_lower, 'price') !== false || strpos($query_lower, 'cost') !== false) {
        $context .= "PRICING:\n";
        foreach ($ksa_knowledge['uniforms'] as $type => $details) {
            $context .= "- {$details['description']}: {$details['total_range']}\n";
        }
    }
    
    if (strpos($query_lower, 'delivery') !== false || strpos($query_lower, 'shipping') !== false) {
        $context .= "DELIVERY:\n";
        foreach ($ksa_knowledge['delivery_info'] as $key => $value) {
            $context .= "- " . ucfirst(str_replace('_', ' ', $key)) . ": $value\n";
        }
    }
    
    if (strpos($query_lower, 'program') !== false || strpos($query_lower, 'age') !== false) {
        $context .= "SCOUTING PROGRAMS:\n";
        foreach ($ksa_knowledge['programs'] as $program => $details) {
            $context .= "- $program: Age {$details['age']}\n";
        }
    }
    
    if (strpos($query_lower, 'track') !== false || strpos($query_lower, 'order') !== false) {
        $context .= "To track your order, visit check.php and enter your M-Pesa phone number.\n";
    }
    
    return $context ?: "I'm here to help with information about KSA uniforms, scouting programs, and services.\n";
}
?>
