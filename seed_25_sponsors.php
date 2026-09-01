<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
use App\Models\Sponsor;
$extra = [
    ['name'=>'Figma','logo_url'=>'https://upload.wikimedia.org/wikipedia/commons/3/33/Figma-logo.svg','website_url'=>'https://figma.com','tagline'=>'Collaborative interface design','sort_order'=>20],
    ['name'=>'Notion','logo_url'=>'https://upload.wikimedia.org/wikipedia/commons/4/45/Notion_app_logo.png','website_url'=>'https://notion.so','tagline'=>'All-in-one workspace','sort_order'=>21],
    ['name'=>'Linear','logo_url'=>'https://cdn.worldvectorlogo.com/logos/linear.svg','website_url'=>'https://linear.app','tagline'=>'Issue tracking for high-performance teams','sort_order'=>22],
    ['name'=>'Stripe','logo_url'=>'https://upload.wikimedia.org/wikipedia/commons/b/ba/Stripe_Logo%2C_revised_2016.svg','website_url'=>'https://stripe.com','tagline'=>'Financial infrastructure for the internet','sort_order'=>23],
    ['name'=>'Supabase','logo_url'=>'https://supabase.com/favicon/favicon-32x32.png','website_url'=>'https://supabase.com','tagline'=>'The open source Firebase alternative','sort_order'=>24],
];
foreach ($extra as $data) {
    Sponsor::updateOrCreate(['name'=>$data['name']], array_merge($data, ['is_active'=>true]));
    echo "Sponsor: {$data['name']} upserted\n";
}
echo "Total sponsors: ".Sponsor::count()."\n";
